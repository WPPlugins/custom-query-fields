<?php
/*
 * Plugin Name: Custom Query Fields
 * Description: Extend your site's querying and sorting functionality using custom field values.
 * Plugin URI: http://wordpress.org/extend/plugins/custom-query-fields/
 * Author: Julian Lannigan
 * Version: 0.1.2b
 * Author URI: http://julianlannigan.com
 * License: GPL2+
 */

function register_custom_queryable_field($fieldName, $options = array()) {
	return CustomQueryFields::registerField($fieldName, $options);
}

class CustomQueryFields {
	
	private $_queryMethod;
	const ORDERBY_VAR = 'order_by';
	
	public function __construct() {
		global $CQF_data;
		if (!is_array($CQF_data)) {
			$CQF_data = array('fields'=>array());
		}
		add_filter('query_vars', array('CustomQueryFields', 'addQueryVars') );
		self::enable();
	}
	
	public static function registerField($fieldName, $options = array()) {
		global $CQF_data;
		$fields =& $CQF_data['fields'];
		
		if (!is_string($fieldName) || $fieldName == "" || isset($fields[$fieldName])) {
			return false;
		}
		$defaults = array(
			'dataType' 	=> 'text',	//[text] [numeric]
			'order'		=> 'DESC',	//[ASC] [DESC]
			'compare'	=> '='		//(ignored for numeric comparsions)
		);
		
		$options = array_merge($defaults, $options);
		
		//Normalize the datatypes
		switch ($options['dataType']) {
			case "numeric":
			case "number":
			case "int":
			case "integer":
				$options['dataType'] = 'numeric';
			break;
			
			case "text":
			case "string":
			case "str":
			default:
				$options['dataType'] = 'text';
		}
		
		$fields[$fieldName] = array('fieldName' => $fieldName, 'options' => $options);
		return true;
	}
	
	public function enable() {
		if (self::is_version('3.1')) {
			add_action('pre_get_posts', array(__CLASS__, 'doQueryParse'));
		} elseif (self::is_version('3.0')) {
			add_filter('posts_join', array(__CLASS__, 'doPostsJoin'));
			add_filter('posts_orderby', array(__CLASS__, 'doPostsOrderBy'));
		}
	}
	
	public function disable() {
		if (self::is_version('3.1')) {
			remove_action('pre_get_posts', array(__CLASS__, 'doQueryParse'));
		} elseif (self::is_version('3.0')) {
			remove_filter('posts_join', array(__CLASS__, 'doPostsJoin'));
			remove_filter('posts_orderby', array(__CLASS__, 'doPostsOrderBy'));
		}
	}
	
	public function addQueryVars($query_vars){
		global $CQF_data;
		$fields =& $CQF_data['fields'];
		
		foreach($fields as $var){
			switch ($var['options']['dataType']) {
				case "numeric":
					$query_vars[] = $var['fieldName']."_min";
					$query_vars[] = $var['fieldName']."_max";
				break;
			}
			$query_vars[] = $var['fieldName'];
		}
		$query_vars[] = self::ORDERBY_VAR;
		return $query_vars;
	}
	
	// For WP version >= 3.1
	public function doQueryParse(&$queryObj, $once = true) {
		if ($queryObj->is_admin || $queryObj->query_vars['suppress_filters'] == true) {
			return;
		}
		
		global $CQF_data;
		$fields =& $CQF_data['fields'];
		
		$queryObj->meta_query = self::genMetaArray();
		$queryObj->set('meta_query', $queryObj->meta_query);
		
		$orderby = get_query_var(self::ORDERBY_VAR);
		if ($orderby && isset($fields[$orderby])) {
			switch ($fields[$orderby]['options']['dataType']) {
				case "numeric":
					$queryObj->set('orderby', 'meta_value_num');
				break;
				
				case "text":
				default:
					$queryObj->set('orderby', 'meta_value');
			}
			$queryObj->set('meta_key', $fields[$orderby]['fieldName']);
			$queryObj->set('order', $fields[$orderby]['options']['order']);
		}
		
		if ($once) {
			remove_action('pre_get_posts', array(__CLASS__, 'doQueryParse'));
		}
		return $queryObj;
	}
	
	// For WP version 3.0 <= x < 3.1
	public function doPostsJoin($join) {
		global $wpdb;
		
		$meta = self::genMetaArray();
		if (count($meta) > 0) {
			$index = 1;
			foreach ($meta as $index=>$field) {
				switch ($field['type']) {
					case "NUMERIC":
						switch ($field['compare']) {
							case "BETWEEN":
								$metaValue = sprintf("'%s' AND '%s'", addslashes($field['value'][0]), addslashes($field['value'][1]));
							break;
							default:
								$metaValue = "'".addslashes($field['value'])."'";
						}
						$cast = "SIGNED";
					break;
					
					case "CHAR":
					default:
						$cast = "CHAR";
						$metaValue = "'%".addslashes($field['value'])."%'";
				}
				
				$join .= sprintf("%sINNER JOIN %s cqf%d ON (cqf%d.post_id = %s.ID AND cqf%d.meta_key = '%s' AND CAST(cqf%d.meta_value AS %s) %s %s)"
					, (($join == "") ? "" : "\n")
					, $wpdb->postmeta
					, $index
					, $index
					, $wpdb->posts
					, $index
					, $field['key']
					, $index
					, $cast
					, $field['compare']
					, $metaValue
				);
			}
		}
		
		if (1==1 || $once) {
			remove_filter('posts_join', array(__CLASS__, 'doPostsJoin'));
		}
		return $join;
	}
	
	// For WP version 3.0 <= x <= 3.0.6
	public function doPostsOrderBy($orderby) {
		global $CQF_data, $wpdb;
		$fields =& $CQF_data['fields'];
		
		$orderby_var = get_query_var(self::ORDERBY_VAR);
		if ($orderby_var && isset($fields[$orderby_var])) {
			$meta = self::genMetaArray();
			foreach ($meta as $index=>$field) {
				if ($field['key'] == $orderby_var) {
					break;
				}
			}
			$orderby = "cqf{$index}.meta_value";
			switch ($fields[$orderby_var]['options']['dataType']) {
				case "numeric":
					$orderby .= "+0";
				break;
			}
			$orderby .= " {$fields[$orderby_var]['options']['order']}";
		}
		
		if (1==1 || $once) {
			remove_filter('posts_orderby', array(__CLASS__, 'doPostsOrderBy'));
		}
		return $orderby;
	}
	
	private static function genMetaArray() {
		global $CQF_data;
		$fields =& $CQF_data['fields'];
		
		$retArray = array();
		
		foreach ($fields as $fieldName=>$field) {
			$value = get_query_var($fieldName);
			switch ($field['options']['dataType']) {
				case "numeric":
					$minLimit = get_query_var($fieldName."_min");
					$maxLimit = get_query_var($fieldName."_max");
					$queryValue = 0;
					$queryCompare = '=';
					$queryType = 'NUMERIC';
					
					if (!$minLimit && !$maxLimit && $value) {
						//Exact value query
						$queryValue = $value;
					} elseif ($minLimit && !$maxLimit && !$value) {
						//Minimum only query
						$queryValue = $minLimit;
						$queryCompare = '>=';
					} elseif (!$minLimit && $maxLimit && !$value) {
						//Maximum only query
						$queryValue = $maxLimit;
						$queryCompare = '<=';
					} elseif ($minLimit && $maxLimit && !$value) {
						//Between limit query
						$queryValue = array($minLimit, $maxLimit);
						$queryCompare = 'BETWEEN';
					} elseif ($minLimit && $maxLimit && $value) {
						//Between limit query (all possible vars are available for some reason through. Limits take precedence)
						$queryValue = array($minLimit, $maxLimit);
						$queryCompare = 'BETWEEN';
					} else {
						continue;
					}
					$retArray[] = array('key' => $fieldName, 'value' => $queryValue, 'compare' => $queryCompare, 'type' => $queryType);
					
				break;
				
				case "text":
				default:
					if ($value) {
						$retArray[] = array('key' => $fieldName, 'value' => $value, 'compare' => $field['options']['compare'], 'type' => 'CHAR');
					}
			}
		}
		
		return $retArray;
	}
	
	public function is_version($version, $operator = '>=') {
		global $wp_version;
		if (version_compare($wp_version, $version, $operator)) {
			return true;
		}
		return false;
	}
	
}

new CustomQueryFields();