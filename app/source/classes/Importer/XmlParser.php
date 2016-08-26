<?php
namespace Postleaf\Importer;

class XmlParser {
    private $file;
    private $authors = array();
    private $posts = array();
    private $categories = array();
    private $tags = array();
    private $media = array();
    
    public function __construct($file){
        $this->file = $file;
    }
    
    public function parse(){
        if (extension_loaded('simplexml')){
            return $this->parseWithSimpleXml();
        } else {
            return $this->parseWithXML();
        }
    }
    // SimpleXML
    private function parseWithSimpleXml(){
        $parser = simplexml_load_file($this->file);
    }
    
    private function parseWithXML(){
        $parser = xml_parser_create();
        xml_parser_set_option( $xml, XML_OPTION_SKIP_WHITE, 1 );
		xml_parser_set_option( $xml, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object($parser, $this);
		xml_set_character_data_handler( $xml, 'cdata' );
		xml_set_element_handler( $xml, 'tag_open', 'tag_close' );
		xml_parse($parser, file_get_contents($this->file), true);
    }
}
?>