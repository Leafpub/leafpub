<?php
namespace Postleaf\Importer;

class Wordpress extends AbstractImporter {
    public function parseFile(){
        $parser = new XmlParser($this->_fileToParse);
        $retArray = $parser->parse();
    }
}
?>