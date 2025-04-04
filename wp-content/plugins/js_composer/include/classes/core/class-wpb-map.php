<?php
/**
 * Mapping plugin functionality.
 *
 * @see https://kb.wpbakery.com/docs/inner-api/vc_map
 *
 * @package WPBakeryPageBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * WPBakery Page Builder Main manager.
 *
 * @since   4.2
 */
class WPBMap {
	/**
	 * Shortcode mapping scope.
	 *
	 * @var string
	 */
	protected static $scope = 'default';

	/**
	 * Array of mapped shortcodes.
	 *
	 * @var array
	 */
	protected static $sc = [];

	/**
	 * Scopes for shortcodes mapping.
	 *
	 * @var array
	 */
	protected static $scopes = [
		'default' => [],
	];

	/**
	 * List of removed shortcode elements.
	 *
	 * @var array
	 */
	protected static $removedElements = [];

	/**
	 * Indicates whether shortcodes are sorted.
	 *
	 * @var bool
	 */
	protected static $sorted_sc = false;

	/**
	 * Cached categories of shortcodes.
	 *
	 * @var array|false
	 */
	protected static $categories = false;

	/**
	 * User-specific shortcode data.
	 *
	 * @var bool
	 */
	protected static $user_sc = false;

	/**
	 * User-specific sorted shortcodes.
	 *
	 * @var bool
	 */
	protected static $user_sorted_sc = false;

	/**
	 * User-specific categories.
	 *
	 * @var bool
	 */
	protected static $user_categories = false;

	/**
	 * Main plugin settings object.
	 *
	 * @var Vc_Settings
	 */
	protected static $settings;

	/**
	 * Current user role.
	 *
	 * @var string
	 */
	protected static $user_role;

	/**
	 * Regular expression for shortcode tags.
	 *
	 * @var string
	 */
	protected static $tags_regexp;

	/**
	 * Indicates whether the class has been initialized.
	 *
	 * @var bool
	 */
	protected static $is_init = false;

	/**
	 * Initialized elements.
	 *
	 * @var array
	 */
	protected static $init_elements = [];

	/**
	 * Scoped initialized elements.
	 *
	 * @var array
	 */
	protected static $init_elements_scoped = [
		'default' => [],
	];

	/**
	 * Set init status for WPMap.
	 *
	 * If $is_init is FALSE, then all activity like add, update and delete for shortcodes attributes will be hold in
	 * the list of activity and will be executed after initialization.
	 *
	 * @param bool $value
	 * @see Vc_Mapper::iniy.
	 * @static
	 */
	public static function setInit( $value = true ) {
		self::$is_init = $value;
	}

	/**
	 * Gets user role and access rules for current user.
	 *
	 * @static
	 * @return mixed
	 */
	protected static function getSettings() {
		global $current_user;

		// @todo fix_roles? what is this and why it is inside class-wpb-map?
		if ( null !== self::$settings ) {
			if ( function_exists( 'wp_get_current_user' ) ) {
				wp_get_current_user();
				/**
				 * Get use group access rules.
				 *
				 * @var Vc_Settings $settings
				 */
				if ( ! empty( $current_user->roles ) ) {
					self::$user_role = $current_user->roles[0];
				} else {
					self::$user_role = 'author';
				}
			} else {
				self::$user_role = 'author';
			}
			self::$settings = vc_settings()->get( 'groups_access_rules' );
		}

		return self::$settings;
	}

	/**
	 * Check is shortcode with a tag mapped to VC.
	 *
	 * @static
	 *
	 * @param string $tag - shortcode tag.
	 *
	 * @return bool
	 */
	public static function exists( $tag ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			if ( isset( self::$scopes[ $currentScope ], self::$scopes[ $currentScope ][ $tag ] ) ) {
				return true;
			}
		}

		return (bool) isset( self::$sc[ $tag ] );
	}

	/**
	 * Map shortcode to VC.
	 *
	 * This method maps shortcode to VC.
	 * You need to shortcode's tag and settings to map correctly.
	 * Default shortcodes are mapped in config/map.php file.
	 * The best way is to call this method with "init" action callback function of WP.
	 *
	 * vc_filter: vc_mapper_tag - to change shortcode tag, arguments 2 ( $tag, $attributes )
	 * vc_filter: vc_mapper_attributes - to change shortcode attributes (like params array), arguments 2 ( $attributes,
	 * $tag ) vc_filter: vc_mapper_attribute - to change singe shortcode param data, arguments 2 ( $attribute, $tag )
	 * vc_filter: vc_mapper_attribute_{PARAM_TYPE} - to change singe shortcode param data by param type, arguments 2 (
	 * $attribute, $tag )
	 *
	 * @static
	 *
	 * @param string $tag
	 * @param array $attributes
	 *
	 * @return bool
	 */
	public static function map( $tag, $attributes ) {
		if ( in_array( $tag, self::$removedElements, true ) ) {
			return false;
		}
		if ( ! self::$is_init ) {
			if ( empty( $attributes['name'] ) ) {
				throw new Exception( sprintf( esc_html__( 'Wrong name for shortcode:%s. Name required', 'js_composer' ), esc_html( $tag ) ) );
			} elseif ( empty( $attributes['base'] ) ) {
				throw new Exception( sprintf( esc_html__( 'Wrong base for shortcode:%s. Base required', 'js_composer' ), esc_html( $tag ) ) );
			} else {
				vc_mapper()->addActivity( 'mapper', 'map', [
					'tag' => $tag,
					'attributes' => $attributes,
					'scope' => self::getScope(),
				] );

				return true;
			}

			return false;
		}
		if ( empty( $attributes['name'] ) ) {
			throw new Exception( sprintf( esc_html__( 'Wrong name for shortcode:%s. Name required', 'js_composer' ), esc_html( $tag ) ) );
		} elseif ( empty( $attributes['base'] ) ) {
			throw new Exception( sprintf( esc_html__( 'Wrong base for shortcode:%s. Base required', 'js_composer' ), esc_html( $tag ) ) );
		} else {
			if ( self::getScope() !== 'default' ) {
				if ( ! isset( self::$scopes[ self::getScope() ] ) ) {
					self::$scopes[ self::getScope() ] = [];
				}
				self::$scopes[ self::getScope() ][ $tag ] = $attributes;
			} else {
				self::$sc[ $tag ] = $attributes;
			}
			// Unset cache class object in case if re-map called.
			if ( Vc_Shortcodes_Manager::getInstance()->isShortcodeClassInitialized( $tag ) ) {
				Vc_Shortcodes_Manager::getInstance()->unsetElementClass( $tag );
			}

			return true;
		}

		return false;
	}

	/**
	 * Lazy method to map shortcode to VC.
	 *
	 * This method maps shortcode to VC.
	 * You can map shortcode settings as you do in self::map method. But also you
	 * can pass function name or file, which will be used to add settings for
	 * element. But this will be done only when element data is really required.
	 *
	 * @static
	 * @param string $tag
	 * @param string $settings_function
	 * @param string $settings_file
	 * @param array $attributes
	 *
	 * @return bool
	 * @since 4.9
	 */
	public static function leanMap( $tag, $settings_function = null, $settings_file = null, $attributes = [] ) {
		if ( in_array( $tag, self::$removedElements, true ) ) {
			return false;
		}
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			if ( ! isset( self::$scopes[ $currentScope ] ) ) {
				self::$scopes[ $currentScope ] = [];
			}
			self::$scopes[ $currentScope ][ $tag ] = $attributes;
			self::$scopes[ $currentScope ][ $tag ]['base'] = $tag;
			if ( is_string( $settings_file ) ) {
				self::$scopes[ $currentScope ][ $tag ]['__vc_settings_file'] = $settings_file;
			}
			if ( ! is_null( $settings_function ) ) {
				self::$scopes[ $currentScope ][ $tag ]['__vc_settings_function'] = $settings_function;
			}
		} else {
			self::$sc[ $tag ] = $attributes;
			self::$sc[ $tag ]['base'] = $tag;
			if ( is_string( $settings_file ) ) {
				self::$sc[ $tag ]['__vc_settings_file'] = $settings_file;
			}
			if ( ! is_null( $settings_function ) ) {
				self::$sc[ $tag ]['__vc_settings_function'] = $settings_function;
			}
		}

		return true;
	}

	/**
	 * Generates list of shortcodes taking into account the access rules for shortcodes from VC Settings page.
	 *
	 * This method parses the list of mapped shortcodes and creates categories list for users.
	 *
	 * @static
	 *
	 * @param bool $force - force data generation even data already generated.
	 * @throws \Exception
	 */
	protected static function generateUserData( $force = false ) {
		if ( ! $force && false !== self::$user_sc && false !== self::$user_categories ) {
			return;
		}

		self::$user_sc = [];
		self::$user_categories = [];
		self::$user_sorted_sc = [];
		$deprecated = 'deprecated';
		$add_deprecated = false;
		if ( is_array( self::$sc ) && ! empty( self::$sc ) ) {
			foreach ( array_keys( self::$sc ) as $name ) {
				self::setElementSettings( $name );
				if ( ! isset( self::$sc[ $name ] ) ) {
					continue;
				}
				$values = self::$sc[ $name ];
				if ( vc_user_access_check_shortcode_all( $name ) ) {
					if ( ! isset( $values['content_element'] ) || true === $values['content_element'] ) {
						$categories = isset( $values['category'] ) ? $values['category'] : '_other_category_';
						$values['_category_ids'] = [];
						if ( isset( $values['deprecated'] ) && false !== $values['deprecated'] ) {
							$add_deprecated = true;
							$values['_category_ids'][] = 'deprecated';
						} elseif ( is_array( $categories ) && ! empty( $categories ) ) {
							foreach ( $categories as $c ) {
								if ( false === array_search( $c, self::$user_categories, true ) ) {
									self::$user_categories[] = $c;
								}
								$values['_category_ids'][] = md5( $c );
							}
						} else {
							if ( false === array_search( $categories, self::$user_categories, true ) ) {
								self::$user_categories[] = $categories;
							}
							$values['_category_ids'][] = md5( $categories );
						}
					}

					if ( ! empty( $values['params'] ) ) {
						$params = $values['params'];
						$values['params'] = [];
						foreach ( $params as $attribute ) {
							$attribute = apply_filters( 'vc_mapper_attribute', $attribute, $name );
							$attribute = apply_filters( 'vc_mapper_attribute_' . $attribute['type'], $attribute, $name );
							$values['params'][] = $attribute;
						}
						$sort = new Vc_Sort( $values['params'] );
						$values['params'] = $sort->sortByKey();
					}

					self::$user_sc[ $name ] = $values;
					self::$user_sorted_sc[] = $values;
				}
			}
		}
		if ( $add_deprecated ) {
			self::$user_categories[] = $deprecated;
		}

		$sort = new Vc_Sort( self::$user_sorted_sc );
		self::$user_sorted_sc = $sort->sortByKey();
	}

	/**
	 * Generates list of shortcodes.
	 *
	 * This method parses the list of mapped shortcodes and creates categories list.
	 *
	 * @static_other_category_
	 *
	 * @param bool $force - force data generation even data already generated.
	 * @throws \Exception
	 */
	protected static function generateData( $force = false ) {
		if ( ! $force && false !== self::$categories ) {
			return;
		}
		foreach ( self::$sc as $tag => $settings ) {
			self::setElementSettings( $tag );
		}
		self::$categories = self::collectCategories( self::$sc );
		$sort = new Vc_Sort( array_values( self::$sc ) );
		self::$sorted_sc = $sort->sortByKey();
	}

	/**
	 * Get mapped shortcode settings.
	 *
	 * @static
	 * @return array
	 */
	public static function getShortCodes() {
		return self::$sc;
	}

	/**
	 * Get mapped shortcode settings.
	 *
	 * @static
	 * @return array
	 * @throws \Exception
	 */
	public static function getAllShortCodes() {
		self::generateData();

		return self::$sc;
	}

	/**
	 * Get mapped shortcode settings.
	 *
	 * @static
	 * @return bool
	 * @throws \Exception
	 */
	public static function getSortedAllShortCodes() {
		self::generateData();

		return self::$sorted_sc;
	}

	/**
	 * Get sorted list of mapped shortcode settings for current user.
	 *
	 * Sorting depends on the weight attribute and mapping order.
	 *
	 * @static
	 * @return bool
	 * @throws \Exception
	 */
	public static function getSortedUserShortCodes() {
		self::generateUserData();

		return self::$user_sorted_sc;
	}

	/**
	 * Get list of mapped shortcode settings for current user.
	 *
	 * @static
	 * @return bool - associated array of shortcodes settings with tag as the key.
	 * @throws \Exception
	 */
	public static function getUserShortCodes() {
		self::generateUserData();

		return self::$user_sc;
	}

	/**
	 * Get mapped shortcode settings by tag.
	 *
	 * @static
	 *
	 * @param string $tag - shortcode tag.
	 *
	 * @return array|null null @since 4.4.3
	 * @throws \Exception
	 */
	public static function getShortCode( $tag ) {
		return self::setElementSettings( $tag );
	}

	/**
	 * Get mapped shortcode settings by tag.
	 *
	 * @param string $tag - shortcode tag.
	 *
	 * @return array|null
	 * @throws \Exception
	 * @since 4.5.2
	 * @static
	 */
	public static function getUserShortCode( $tag ) {
		self::generateUserData();
		if ( isset( self::$user_sc[ $tag ] ) && is_array( self::$user_sc[ $tag ] ) ) {
			$shortcode = self::$user_sc[ $tag ];
			if ( ! empty( $shortcode['params'] ) ) {
				$params = $shortcode['params'];
				$shortcode['params'] = [];
				foreach ( $params as $attribute ) {
					$attribute = apply_filters( 'vc_mapper_attribute', $attribute, $tag );
					$attribute = apply_filters( 'vc_mapper_attribute_' . $attribute['type'], $attribute, $tag );
					$shortcode['params'][] = $attribute;
				}
				$sort = new Vc_Sort( $shortcode['params'] );
				$shortcode['params'] = $sort->sortByKey();
			}

			return $shortcode;
		}

		return null;
	}

	/**
	 * Get all categories for mapped shortcodes.
	 *
	 * @static
	 * @return array
	 * @throws \Exception
	 */
	public static function getCategories() {
		self::generateData();

		return self::$categories;
	}

	/**
	 * Get all categories for current user.
	 *
	 * Category is added to the list when at least one shortcode of this category is allowed for current user
	 * by Vc access rules.
	 *
	 * @static
	 * @return bool
	 * @throws \Exception
	 */
	public static function getUserCategories() {
		self::generateUserData();

		return self::$user_categories;
	}

	/**
	 * Drop shortcode param.
	 *
	 * @static
	 *
	 * @param string $name
	 * @param string $attribute_name
	 *
	 * @return bool
	 */
	public static function dropParam( $name, $attribute_name ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			self::setScope( 'default' );
			$res = self::dropParam( $name, $attribute_name );
			self::setScope( $currentScope );

			return $res;
		}
		if ( ! isset( self::$init_elements[ $name ] ) ) {
			vc_mapper()->addElementActivity( $name, 'drop_param', [
				'name' => $name,
				'attribute_name' => $attribute_name,
			] );

			return true;
		}
		if ( isset( self::$sc[ $name ], self::$sc[ $name ]['params'] ) && is_array( self::$sc[ $name ]['params'] ) ) {
			foreach ( self::$sc[ $name ]['params'] as $index => $param ) {
				if ( $param['param_name'] === $attribute_name ) {
					unset( self::$sc[ $name ]['params'][ $index ] );
					// fix indexes.
					self::$sc[ $name ]['params'] = array_merge( self::$sc[ $name ]['params'] );

					return true;
				}
			}
		}

		return true;
	}

	/**
	 * Returns param settings for mapped shortcodes.
	 *
	 * @static
	 *
	 * @param string $tag
	 * @param string $param_name
	 *
	 * @return bool| array
	 * @throws \Exception
	 */
	public static function getParam( $tag, $param_name ) {
		$currentScope = self::getScope();
		$element = false;
		if ( 'default' !== $currentScope ) {
			if ( isset( self::$scopes[ $currentScope ][ $tag ], self::$scopes[ $currentScope ][ $tag ] ) ) {
				$element = self::$scopes[ $currentScope ][ $tag ];
			}
		}
		if ( ! $element && isset( self::$sc[ $tag ] ) ) {
			$element = self::$sc[ $tag ];
		}
		if ( ! $element ) {
			// No element found.
			return false;
		}

		if ( isset( $element['__vc_settings_function'] ) || isset( $element['__vc_settings_file'] ) ) {
			$element = self::setElementSettings( $tag );
		}

		if ( ! isset( $element['params'] ) ) {
			return false;
		}

		foreach ( $element['params'] as $index => $param ) {
			if ( $param['param_name'] === $param_name ) {
				return $element['params'][ $index ];
			}
		}

		return false;
	}

	/**
	 * Add new param to shortcode params list.
	 *
	 * @static
	 *
	 * @param string $name
	 * @param array $attribute
	 *
	 * @return bool - true if added, false if scheduled/rejected
	 */
	public static function addParam( $name, $attribute = [] ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			self::setScope( 'default' );
			$res = self::addParam( $name, $attribute );
			self::setScope( $currentScope );

			return $res;
		}
		if ( ! isset( self::$init_elements[ $name ] ) ) {
			vc_mapper()->addElementActivity( $name, 'add_param', [
				'name' => $name,
				'attribute' => $attribute,
			] );

			return false;
		}
		if ( ! isset( self::$sc[ $name ] ) ) {
			// No shortcode found.
			return false;
		} elseif ( ! isset( $attribute['param_name'] ) ) {
			throw new Exception( sprintf( esc_html__( "Wrong attribute for '%s' shortcode. Attribute 'param_name' required", 'js_composer' ), esc_html( $name ) ) );
		} else {

			$replaced = false;

			if ( is_array( self::$sc[ $name ]['params'] ) ) {
				foreach ( self::$sc[ $name ]['params'] as $index => $param ) {
					if ( $param['param_name'] === $attribute['param_name'] ) {
						$replaced = true;
						self::$sc[ $name ]['params'][ $index ] = $attribute;
						break;
					}
				}
			} else {
				self::$sc[ $name ]['params'] = [];
			}
			if ( false === $replaced ) {
				self::$sc[ $name ]['params'][] = $attribute;
			}

			$sort = new Vc_Sort( self::$sc[ $name ]['params'] );
			self::$sc[ $name ]['params'] = $sort->sortByKey();

			return true;
		}

		return false;
	}

	/**
	 * Change param attributes of mapped shortcode.
	 *
	 * @static
	 *
	 * @param string $name
	 * @param array $attribute
	 *
	 * @return bool
	 */
	public static function mutateParam( $name, $attribute = [] ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			self::setScope( 'default' );
			$res = self::mutateParam( $name, $attribute );
			self::setScope( $currentScope );

			return $res;
		}
		if ( ! isset( self::$init_elements[ $name ] ) ) {
			vc_mapper()->addElementActivity( $name, 'mutate_param', [
				'name' => $name,
				'attribute' => $attribute,
			] );

			return false;
		}
		if ( ! isset( self::$sc[ $name ] ) ) {
			// No shortcode found.
			return false;
		} elseif ( ! isset( $attribute['param_name'] ) ) {
			throw new Exception( sprintf( esc_html__( "Wrong attribute for '%s' shortcode. Attribute 'param_name' required", 'js_composer' ), esc_html( $name ) ) );
		} else {

			$replaced = false;

			foreach ( self::$sc[ $name ]['params'] as $index => $param ) {
				if ( $param['param_name'] === $attribute['param_name'] ) {
					$replaced = true;
					self::$sc[ $name ]['params'][ $index ] = array_merge( $param, $attribute );
					break;
				}
			}

			if ( false === $replaced ) {
				self::$sc[ $name ]['params'][] = $attribute;
			}
			$sort = new Vc_Sort( self::$sc[ $name ]['params'] );
			self::$sc[ $name ]['params'] = $sort->sortByKey();
		}

		return true;
	}

	/**
	 * Removes shortcode from mapping list.
	 *
	 * @static
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function dropShortcode( $name ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			self::setScope( 'default' );
			$res = self::dropShortcode( $name );
			self::setScope( $currentScope );

			return $res;
		}
		self::$removedElements[] = $name;
		if ( ! isset( self::$init_elements[ $name ] ) ) {
			vc_mapper()->addElementActivity( $name, 'drop_shortcode', [
				'name' => $name,
			] );
		}
		unset( self::$sc[ $name ] );
		wpbakery()->removeShortCode( $name );

		return true;
	}

	/**
	 * Drop all shortcodes.
	 *
	 * @return bool
	 */
	public static function dropAllShortcodes() {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			self::setScope( 'default' );
			$res = self::dropAllShortcodes();
			self::setScope( $currentScope );

			return $res;
		}
		if ( ! self::$is_init ) {
			vc_mapper()->addActivity( '*', 'drop_all_shortcodes', [] );

			return false;
		}
		foreach ( self::$sc as $name => $data ) {
			wpbakery()->removeShortCode( $name );
		}
		self::$sc = [];
		self::$user_sc = false;
		self::$user_categories = false;
		self::$user_sorted_sc = false;

		return true;
	}

	/**
	 * Modify shortcode's mapped settings.
	 * You can modify only one option of the group options.
	 * Call this method with $settings_name param as associated array to mass modifications.
	 *
	 * @static
	 *
	 * @param string $name - shortcode' name.
	 * @param string $setting_name - option key name or the array of options.
	 * @param string $value - value of settings if $setting_name is option key.
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public static function modify( $name, $setting_name, $value = '' ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			self::setScope( 'default' );
			$res = self::modify( $name, $setting_name, $value );
			self::setScope( $currentScope );

			return $res;
		}
		if ( ! isset( self::$init_elements[ $name ] ) ) {
			vc_mapper()->addElementActivity( $name, 'modify', [
				'name' => $name,
				'setting_name' => $setting_name,
				'value' => $value,
			] );

			return false;
		}
		if ( ! isset( self::$sc[ $name ] ) ) {
			// No shortcode found.
			return false;
		} elseif ( 'base' === $setting_name ) {
			throw new Exception( sprintf( esc_html__( "Wrong setting_name for shortcode:%s. Base can't be modified.", 'js_composer' ), esc_html( $name ) ) );
		}
		if ( is_array( $setting_name ) ) {
			foreach ( $setting_name as $key => $value ) {
				self::modify( $name, $key, $value );
			}
		} else {
			if ( is_array( $value ) ) {
				// fix indexes.
				$value = array_merge( $value );
			}
			self::$sc[ $name ][ $setting_name ] = $value;
			wpbakery()->updateShortcodeSetting( $name, $setting_name, $value );
		}

		return self::$sc;
	}

	/**
	 * Returns "|" separated list of mapped shortcode tags.
	 *
	 * @static
	 * @return string
	 */
	public static function getTagsRegexp() {
		if ( empty( self::$tags_regexp ) ) {
			self::$tags_regexp = implode( '|', array_keys( self::$sc ) );
		}

		return self::$tags_regexp;
	}

	/**
	 * Sorting method for WPBMap::generateUserData method. Called by uasort php function.
	 *
	 * @param array $a
	 * @param array $b
	 *
	 * @return int
	 * @deprecated - use Vc_Sort::sortByKey since 4.4
	 * @static
	 */
	public static function sort( $a, $b ) {
		$a_weight = isset( $a['weight'] ) ? (int) $a['weight'] : 0;
		$b_weight = isset( $b['weight'] ) ? (int) $b['weight'] : 0;
		if ( $a_weight === $b_weight ) {
			// @codingStandardsIgnoreLine
			$cmpa = array_search( $a, (array) self::$user_sorted_sc, true );
			// @codingStandardsIgnoreLine
			$cmpb = array_search( $b, (array) self::$user_sorted_sc, true );

			return ( $cmpa > $cmpb ) ? 1 : - 1;
		}

		return ( $a_weight < $b_weight ) ? 1 : - 1;
	}

	/**
	 * Collect categories for shortcodes.
	 *
	 * @param array $shortcodes
	 * @return array
	 */
	public static function collectCategories( &$shortcodes ) {
		$categories_list = [];
		$deprecated = 'deprecated';
		$add_deprecated = false;
		if ( is_array( $shortcodes ) && ! empty( $shortcodes ) ) {
			foreach ( $shortcodes as $name => $values ) {
				$values['_category_ids'] = [];
				if ( isset( $values['deprecated'] ) && false !== $values['deprecated'] ) {
					$add_deprecated = true;
					$values['_category_ids'][] = 'deprecated';
				} elseif ( isset( $values['category'] ) ) {
					$categories = $values['category'];
					if ( is_array( $categories ) && ! empty( $categories ) ) {
						foreach ( $categories as $c ) {
							if ( false === array_search( $c, $categories_list, true ) ) {
								$categories[] = $c;
							}
							$values['_category_ids'][] = md5( $c );
						}
					} else {
						if ( false === array_search( $categories, $categories_list, true ) ) {
							$categories_list[] = $categories;
						}
						// string $categories - unique id's for category.
						$values['_category_ids'][] = md5( $categories );
					}
				}
				$shortcodes[ $name ] = $values;
			}
		}
		if ( $add_deprecated ) {
			$categories_list[] = $deprecated;
		}

		return $categories_list;
	}

	/**
	 * Process files/functions for lean mapping settings
	 *
	 * @param string $tag
	 *
	 * @return array|null
	 * @throws \Exception
	 * @since 4.9
	 */
	public static function setElementSettings( $tag ) {
		$currentScope = self::getScope();
		if ( 'default' !== $currentScope ) {
			if ( isset( self::$scopes[ $currentScope ], self::$scopes[ $currentScope ][ $tag ] ) && ! in_array( $tag, self::$removedElements, true ) ) {
				if ( isset( self::$init_elements_scoped[ $currentScope ], self::$init_elements_scoped[ $currentScope ][ $tag ] ) && ! empty( self::$init_elements_scoped[ $currentScope ][ $tag ] ) ) {
					return self::$scopes[ $currentScope ][ $tag ];
				}

				$settings = self::$scopes[ $currentScope ][ $tag ];
				if ( isset( $settings['__vc_settings_function'] ) ) {
					self::$scopes[ $currentScope ][ $tag ] = call_user_func( $settings['__vc_settings_function'], $tag );
				} elseif ( isset( $settings['__vc_settings_file'] ) ) {
					self::$scopes[ $currentScope ][ $tag ] = include $settings['__vc_settings_file'];
				}
				self::$scopes[ $currentScope ][ $tag ]['base'] = $tag;
				self::$init_elements_scoped[ $currentScope ][ $tag ] = true;
				vc_mapper()->callElementActivities( $tag );

				return self::$scopes[ $currentScope ][ $tag ];
			}
		}
		if ( ! isset( self::$sc[ $tag ] ) || in_array( $tag, self::$removedElements, true ) ) {
			return null;
		}
		if ( isset( self::$init_elements[ $tag ] ) && self::$init_elements[ $tag ] ) {
			return self::$sc[ $tag ];
		}
		$settings = self::$sc[ $tag ];
		if ( isset( $settings['__vc_settings_function'] ) ) {
			self::$sc[ $tag ] = call_user_func( $settings['__vc_settings_function'], $tag );
		} elseif ( isset( $settings['__vc_settings_file'] ) ) {
			self::$sc[ $tag ] = include $settings['__vc_settings_file'];
		}
		self::$sc[ $tag ] = apply_filters( 'vc_element_settings_filter', self::$sc[ $tag ], $tag );
		self::$sc[ $tag ]['base'] = $tag;
		self::$init_elements[ $tag ] = true;
		vc_mapper()->callElementActivities( $tag );

		return self::$sc[ $tag ];
	}

	/**
	 * Add elements as shortcodes
	 *
	 * @since 4.9
	 */
	public static function addAllMappedShortcodes() {
		foreach ( self::$sc as $tag => $settings ) {
			if ( ! in_array( $tag, self::$removedElements, true ) && ! shortcode_exists( $tag ) ) {
				add_shortcode( $tag, 'vc_do_shortcode' );
			}
		}

		// Map also custom scopes.
		foreach ( self::$scopes as $scopeName => $scopeElements ) {
			foreach ( $scopeElements as $tag => $settings ) {
				if ( ! in_array( $tag, self::$removedElements, true ) && ! shortcode_exists( $tag ) ) {
					add_shortcode( $tag, 'vc_do_shortcode' );
				}
			}
		}
	}

	/**
	 * Set scopes.
	 *
	 * @param string $scope
	 */
	public static function setScope( $scope = 'default' ) {
		if ( ! isset( self::$scopes[ $scope ] ) ) {
			self::$scopes[ $scope ] = [];
			self::$init_elements_scoped[ $scope ] = [];
		}
		self::$scope = $scope;
	}

	/**
	 * Reset scope to default.
	 */
	public static function resetScope() {
		self::$scope = 'default';
	}

	/**
	 * Get scope.
	 *
	 * @return string
	 */
	public static function getScope() {
		return self::$scope;
	}
}
