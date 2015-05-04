<?php

namespace Duke;

use \Nette\Utils\FileSystem as FS;
use \Nette\Utils\Finder as F;
Use \Nette\Utils\Image as I;
use \Cdc\ArrayHelper as AH;
use \Nette\Utils\Arrays as A;
use \C as C;

class Definition extends \Cdc\Definition {

    /**
     * Cache de presets de imagens. Why not?
     * @var array
     */
    private static $_presets;

    public function textBlock($rowset, $options = array()) {
        return null;
    }

    public function options($row, $rowset, $options = array()) {
        $text = '';

        $primary = AH::current($this->query(self::TYPE_COLUMN)->byKey('primary')->fetch(self::MODE_KEY_ONLY));

        $items = array();

        if (isset($options['routes']['update'])) {
            $items[label('Editar')] = array('class' => 'btn btn-sm btn-default', 'icon' => 'fa fa-pencil', 'route' => $options['routes']['update']);
        }

        if (isset($options['routes']['delete'])) {
            $items[label('Excluir')] = array('class' => 'btn btn-sm btn-danger', 'icon' => 'fa fa-trash', 'route' => $options['routes']['delete']);
        }

        foreach ($items as $label => $data) {
            $link = \C::$dispatcher->getRouter()->generate($data['route'], array($primary => $row[$primary]));
            $text .= sprintf('<a class="%s" title="%s" href="%s"><i class="%s"></i></a>', $data['class'], $label, $link, $data['icon']);
        }

        return $text;
    }

    /**
     * Saves file to disk and creates a database row with it's info.
     *
     * You should override processFile if you need to edit images and such.
     *
     * @param array $item Hydrated array
     * @param \Nette\Http\FileUpload $file
     * @param type $preset
     * @return array New database row
     */
    public function saveFile($item, \Nette\Http\FileUpload $file, $preset) {

        $presets = static::getPresets();

        $col = $this->definition[$preset];

        $multifile = $col[self::TYPE_WIDGET]['widget'] == 'multifile';

        if (!$multifile) {
            if (isset($item[$preset])) {
                $ids = \Cdc\ArrayHelper::pluck($item[$preset], 'id');
                $this->deleteFiles($ids, $preset);
            }
        }

        $pathInfo = pathinfo($file->getName());
        $ext = A::get($pathInfo, 'extension');

        $data = array(
            'nome_original' => $file->getName(),
            'mime' => $file->getContentType(),
            'usuario_id' => C::$user->id,
            'preset_id' => $presets[$preset]['id'],
        );

        $ins = new \Cdc\Sql\Insert(C::connection());
        $ins->from(array('arquivo'))->cols($data)->stmt();
        $id = $this->lastInsertId('arquivo', 'id');

        $fileRow = $data;
        $fileRow['id'] = $id;

        $metadata = array();

        $newFileName = sprintf('%s.%s', $fileRow['id'], $ext);

        $isImage = $file->isImage();

        if ($isImage) {
            $imageSize = $file->getImageSize();
            $metadata = array(
                'width' => $imageSize[0],
                'height' => $imageSize[1],
            );
        }

        $updateData = array(
            'nome' => $newFileName,
            'metadados' => json_encode($metadata),
            'imagem' => $isImage,
        );

        $u = new \Cdc\Sql\Update(C::connection());
        $u->from(array('arquivo'))->cols($updateData)->where(array('id =' => $id))->stmt();

        FS::copy($file->getTemporaryFile(), C::$upload_abs . $newFileName, true);

        $data = array_merge($updateData, $fileRow);

        $this->processFile($item, $data, $preset);

        return $fileRow;
    }

    public function deleteFiles($ids, $preset = false) {
        if (!$ids) {
            return;
        }
        if ($preset) {
            $presets = static::getPresets();
            $preset_id = $presets[$preset]['id'];
        } else {
            $preset_id = false;
        }

        $deleteList = array();

        $select = new \Cdc\Sql\Select(C::connection());
        $arquivos = $select->from(array('arquivo'))->cols(array('id', 'preset_id'))->where(array('id in' => $ids))->stmt()->fetchAll(\PDO::FETCH_KEY_PAIR);

        foreach ($arquivos as $_arq => $_preset) {

            if ($preset_id) {
                if ($preset_id != $_preset) {
                    continue;
                }
            }

            $deleteList[] = $_arq;

            foreach (F::findFiles($_arq . '.*', '*-' . $_arq . '.*', $_arq)->from(C::$upload_abs) as $file) {
                unlink($file->getRealPath());
            }
        }
        $del = new \Cdc\Sql\Delete(C::connection());
        $del->from(array('arquivo'))->where(array('id in' => $deleteList))->stmt();
    }

    public static function getPresets() {
        if (!self::$_presets) {
            $select = new \Cdc\Sql\Select(C::connection());
            $p = $select->from(array('preset'))->cols(array('*'))->stmt();
            foreach ($p as $ar) {
                $ar['metadados'] = json_decode($ar['metadados'], true);
                self::$_presets[$ar['slug']] = $ar;
            }
        }
        return self::$_presets;
    }

    public function getFile($row) {

        $teste = reset($row);

        if (is_array($teste)) {
            $row = $teste;
        }

        return C::$upload . $row['nome'];
    }

    public function getImg($row, $preset, $attribs = '', $wh = true) {

        $teste = reset($row);

        if (is_array($teste)) {
            $row = $teste;
        }

        $presets = static::getPresets();

        $metadata = $presets[$preset]['metadados'];

        $p = $this->ensurePreset($row, $preset);

        $fileName = C::$upload . $p;
        $original = f($row, 'nome_original');
        if ($wh) {
            $wh = sprintf(' width="%s" height="%s"', $metadata['width'], $metadata['height']);
        } else {
            $wh = '';
        }

        return sprintf('<img src="%s" ' . $attribs . $wh . ' alt="%s">', $fileName, $original);
    }

    public function ensurePreset($row, $preset) {

        $teste = reset($row);

        if (is_array($teste)) {
            $row = $teste;
        }

        if (!$row) {
            return null;
        }


        $fileName = C::$upload_abs . $row['nome'];

        if (!$row['imagem']) {
            return $row['nome'];
        }

        $presets = static::getPresets();
        $p = A::get($presets, $preset);
        $metadata = A::get($p, 'metadados');

        $presetName = $preset . '-' . $row['nome'];

        if (file_exists(C::$upload_abs . $presetName)) {
            return $presetName;
        }

        $imageMetadata = json_decode($row['metadados'], true);

        if (!file_exists($fileName)) {
            return $presetName;
        }

        $image = I::fromFile($fileName);


        if ($metadata['width'] == $imageMetadata['width'] && $metadata['height'] == $imageMetadata['height']) {
            $image->save(C::$upload_abs . $presetName);
            return $presetName;
        }

        $dim = max(array($metadata['width'], $metadata['height']));

        $canvas = I::fromBlank($dim, $dim, array('red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 127));
        $canvas->place($image->resize($metadata['width'], $metadata['height'], I::FIT), '50%', '50%');
        $canvas->crop('50%', '50%', $metadata['width'], $metadata['height']);
        $canvas->save(C::$upload_abs . $presetName);

        return $presetName;
    }

    public function processFile($item, $fileRow, $preset) {

        if ($fileRow['imagem']) {
            return $this->ensurePreset($fileRow, $preset);
        }

        return $fileRow['nome'];
    }

    public function getDescription() {
        return '&nbsp;';
    }

    public function kv($table, $key, $value, $order = array()) {
        $sql = new \Cdc\Sql\Select(\C::connection());
        return $sql->from(array($table))->cols(array($key, $value))->order($order)->stmt()->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function lastInsertId($table = null, $primary = null) {
        $pdo = \C::connection();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver == 'pgsql') {
            if (!$table) {
                $table = $this->query(self::TYPE_RELATION)->fetch(self::MODE_SINGLE);
            }

            if (!$primary) {
                $primary = $this->query(self::TYPE_COLUMN)->byTag('primary')->fetch(self::MODE_SINGLE);
            }

            $seq = $pdo->query("select pg_get_serial_sequence('$table', '$primary')")->fetch(\PDO::FETCH_COLUMN);
        } else {
            $seq = null;
        }

        return $pdo->lastInsertId($seq);
    }

}
