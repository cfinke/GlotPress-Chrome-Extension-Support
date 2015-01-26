<?php

class GP_Chrome_Extension_Support extends GP_Plugin {
	public $id = 'chrome-extension-support';

	public $errors  = array();
	public $notices = array();

	public function __construct() {
		parent::__construct();
	}
}

GP::$plugins->gp_chrome_extension_support = new GP_Chrome_Extension_Support;

class Chrome_Extension_Entry extends Translation_Entry {
	var $_key;
	
	function key() {
		return $this->_key;
	}
}

class Chrome_Extension_Locale extends Translations {
	/**
	 * Exports the whole file as a string
	 *
	 * @return string
	 */
	function export() {
		$json = array();
		
		foreach ( $this->entries as $entry ) {
			$json_entry = array();
			$json_entry['messages'] = $entry->singular;
			
			if ( isset( $entry->context ) ) {
				$json_entry['description'] = $entry->context;
			}
			
			$json[$entry->key()] = $json_entry;
		}
		
		return json_encode( $json );
	}

	/**
	 * Same as {@link export}, but writes the result to a file
	 *
	 * @param string $filename where to write the string
	 * @return bool true on success, false on error
	 */
	function export_to_file($filename) {
		$fh = fopen($filename, 'w');
		if (false === $fh) return false;
		$export = $this->export();
		$res = fwrite($fh, $export);
		if (false === $res) return false;
		return fclose($fh);
	}

	function import_from_file( $filename ) {
		$messages_text = file_get_contents( $filename );
		$messages_text = $this->convert_to_unicode( $messages_text );
		$messages_json = json_decode( $messages_text );
		
		if ( ! $messages_json ) {
			return false;
		}
		
		foreach ( $messages_json as $key => $data ) {
			$entry = new Chrome_Extension_Entry();
			$entry->singular = $data->message;
			$entry->_key = $key;
			
			if ( isset( $data->description ) ) {
				$entry->context = $data->description;
			}
			
			// $this->add_comment_to_entry( $entry, $line );
			// $entry->context .= ...;
			// $entry->singular .= ...;
			// $entry->plural .= ...;
			// $entry->is_plural = false;
			// $entry->translations = array( ... );

			$this->add_entry( $entry );
		}
		
		return $this;
	}
	
	private function convert_to_unicode( $str ) {
		if ( ! mb_check_encoding( $str, "UTF-8" ) ) {
			$str = mb_convert_encoding( $str, "UTF-8" );
		}
	
		return $str;
	}
}

class GP_Format_Chrome extends GP_Format {

	public $name = 'Chrome Extension';
	public $extension = 'json';

	public $class = 'Chrome_Extension_Locale';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$json = new $this->class;
		
		// force export only current translations
		$filters = array();
		$filters['status'] = 'current';

		foreach( $entries as $entry ) {
			$json->add_entry( $entry );
		}
		
		return $json->export();
	}

	public function read_originals_from_file( $file_name ) {
		$json = new $this->class;
		
		return $json->import_from_file( $file_name );
	}

}

GP::$formats['chrome'] = new GP_Format_Chrome;