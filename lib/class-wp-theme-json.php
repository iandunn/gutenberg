<?php
/**
 * Process of structures that adhere to the theme.json schema.
 *
 * @package gutenberg
 */

/**
 * Class that encapsulates the processing of
 * structures that adhere to the theme.json spec.
 */
class WP_Theme_JSON {

	/**
	 * Container of data in theme.json format.
	 *
	 * @var array
	 */
	private $theme_json = null;

	/**
	 * Holds block metadata extracted from block.json
	 * to be shared among all instances so we don't
	 * process it twice.
	 *
	 * @var array
	 */
	private static $blocks_metadata = null;

	/**
	 * How to address all the blocks
	 * in the theme.json file.
	 */
	const ALL_BLOCKS_NAME = 'defaults';

	/**
	 * The CSS selector for the * block,
	 * only using to generate presets.
	 *
	 * @var string
	 */
	const ALL_BLOCKS_SELECTOR = ':root';

	/**
	 * How to address the root block
	 * in the theme.json file.
	 *
	 * @var string
	 */
	const ROOT_BLOCK_NAME = 'root';

	/**
	 * The CSS selector for the root block.
	 *
	 * @var string
	 */
	const ROOT_BLOCK_SELECTOR = ':root';

	/**
	 * Data schema of each block within a theme.json.
	 *
	 * Example:
	 *
	 * {
	 *   'block-one': {
	 *     'styles': {
	 *       'color': {
	 *         'background': 'color'
	 *       }
	 *     },
	 *     'settings': {
	 *       'color': {
	 *         'custom': true
	 *       }
	 *     }
	 *   },
	 *   'block-two': {
	 *     'styles': {
	 *       'color': {
	 *         'link': 'color'
	 *       }
	 *     }
	 *   }
	 * }
	 */
	const SCHEMA = array(
		'customTemplates' => null,
		'templateParts'   => null,
		'styles'          => array(
			'border'     => array(
				'radius' => null,
				'color'  => null,
				'style'  => null,
				'width'  => null,
			),
			'color'      => array(
				'background' => null,
				'gradient'   => null,
				'link'       => null,
				'text'       => null,
			),
			'spacing'    => array(
				'padding' => array(
					'top'    => null,
					'right'  => null,
					'bottom' => null,
					'left'   => null,
				),
			),
			'typography' => array(
				'fontFamily'     => null,
				'fontSize'       => null,
				'fontStyle'      => null,
				'fontWeight'     => null,
				'lineHeight'     => null,
				'textDecoration' => null,
				'textTransform'  => null,
			),
		),
		'settings'        => array(
			'border'     => array(
				'customRadius' => null,
				'customColor'  => null,
				'customStyle'  => null,
				'customWidth'  => null,
			),
			'color'      => array(
				'custom'         => null,
				'customGradient' => null,
				'gradients'      => null,
				'link'           => null,
				'palette'        => null,
			),
			'spacing'    => array(
				'customPadding' => null,
				'units'         => null,
			),
			'typography' => array(
				'customFontSize'        => null,
				'customLineHeight'      => null,
				'dropCap'               => null,
				'fontFamilies'          => null,
				'fontSizes'             => null,
				'customFontStyle'       => null,
				'customFontWeight'      => null,
				'customTextDecorations' => null,
				'customTextTransforms'  => null,
			),
			'custom'     => null,
			'layout'     => null,
		),
	);

	/**
	 * Presets are a set of values that serve
	 * to bootstrap some styles: colors, font sizes, etc.
	 *
	 * They are a unkeyed array of values such as:
	 *
	 * ```php
	 * array(
	 *   array(
	 *     'slug'      => 'unique-name-within-the-set',
	 *     'name'      => 'Name for the UI',
	 *     <value_key> => 'value'
	 *   ),
	 * )
	 * ```
	 *
	 * This contains the necessary metadata to process them:
	 *
	 * - path          => where to find the preset within the settings section
	 *
	 * - value_key     => the key that represents the value
	 *
	 * - css_var_infix => infix to use in generating the CSS Custom Property. Example:
	 *                   --wp--preset--<preset_infix>--<slug>: <preset_value>
	 *
	 * - classes      => array containing a structure with the classes to
	 *                   generate for the presets. Each class should have
	 *                   the class suffix and the property name. Example:
	 *
	 *                   .has-<slug>-<class_suffix> {
	 *                       <property_name>: <preset_value>
	 *                   }
	 */
	const PRESETS_METADATA = array(
		array(
			'path'          => array( 'color', 'palette' ),
			'value_key'     => 'color',
			'css_var_infix' => 'color',
			'classes'       => array(
				array(
					'class_suffix'  => 'color',
					'property_name' => 'color',
				),
				array(
					'class_suffix'  => 'background-color',
					'property_name' => 'background-color',
				),
			),
		),
		array(
			'path'          => array( 'color', 'gradients' ),
			'value_key'     => 'gradient',
			'css_var_infix' => 'gradient',
			'classes'       => array(
				array(
					'class_suffix'  => 'gradient-background',
					'property_name' => 'background',
				),
			),
		),
		array(
			'path'          => array( 'typography', 'fontSizes' ),
			'value_key'     => 'size',
			'css_var_infix' => 'font-size',
			'classes'       => array(
				array(
					'class_suffix'  => 'font-size',
					'property_name' => 'font-size',
				),
			),
		),
		array(
			'path'          => array( 'typography', 'fontFamilies' ),
			'value_key'     => 'fontFamily',
			'css_var_infix' => 'font-family',
			'classes'       => array(),
		),
	);

	/**
	 * Metadata for style properties.
	 *
	 * Each property declares:
	 *
	 * - 'value': path to the value in theme.json and block attributes.
	 */
	const PROPERTIES_METADATA = array(
		'--wp--style--color--link' => array(
			'value' => array( 'color', 'link' ),
		),
		'background'               => array(
			'value' => array( 'color', 'gradient' ),
		),
		'background-color'         => array(
			'value' => array( 'color', 'background' ),
		),
		'border-radius'            => array(
			'value' => array( 'border', 'radius' ),
		),
		'border-color'             => array(
			'value' => array( 'border', 'color' ),
		),
		'border-width'             => array(
			'value' => array( 'border', 'width' ),
		),
		'border-style'             => array(
			'value' => array( 'border', 'style' ),
		),
		'color'                    => array(
			'value' => array( 'color', 'text' ),
		),
		'font-family'              => array(
			'value' => array( 'typography', 'fontFamily' ),
		),
		'font-size'                => array(
			'value' => array( 'typography', 'fontSize' ),
		),
		'font-style'               => array(
			'value' => array( 'typography', 'fontStyle' ),
		),
		'font-weight'              => array(
			'value' => array( 'typography', 'fontWeight' ),
		),
		'line-height'              => array(
			'value' => array( 'typography', 'lineHeight' ),
		),
		'padding'                  => array(
			'value'      => array( 'spacing', 'padding' ),
			'properties' => array( 'top', 'right', 'bottom', 'left' ),
		),
		'text-decoration'          => array(
			'value' => array( 'typography', 'textDecoration' ),
		),
		'text-transform'           => array(
			'value' => array( 'typography', 'textTransform' ),
		),
	);

	/**
	 * Constructor.
	 *
	 * @param array $theme_json A structure that follows the theme.json schema.
	 */
	public function __construct( $theme_json = array() ) {
		$this->theme_json = array();

		if ( ! is_array( $theme_json ) ) {
			return;
		}

		// Remove top-level keys that aren't present in the schema.
		$this->theme_json = array_intersect_key( $theme_json, self::SCHEMA );

		$block_metadata = self::get_blocks_metadata();
		foreach ( array( 'settings', 'styles' ) as $subtree ) {
			// Remove settings & styles subtrees if they aren't arrays.
			if ( isset( $this->theme_json[ $subtree ] ) && ! is_array( $this->theme_json[ $subtree ] ) ) {
				unset( $this->theme_json[ $subtree ] );
			}

			// Remove block selectors subtrees declared within settings & styles if that aren't registered.
			if ( isset( $this->theme_json[ $subtree ] ) ) {
				$this->theme_json[ $subtree ] = array_intersect_key( $this->theme_json[ $subtree ], $block_metadata );
			}
		}

		foreach ( $block_metadata as $block_selector => $metadata ) {
			if ( isset( $this->theme_json['styles'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $this->theme_json['styles'][ $block_selector ] ) ) {
					unset( $this->theme_json['styles'][ $block_selector ] );
					continue;
				}

				$styles_schema                                 = self::SCHEMA['styles'];
				$this->theme_json['styles'][ $block_selector ] = self::remove_keys_not_in_schema(
					$this->theme_json['styles'][ $block_selector ],
					$styles_schema
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $this->theme_json['styles'][ $block_selector ] ) ) {
					unset( $this->theme_json['styles'][ $block_selector ] );
				}
			}

			if ( isset( $this->theme_json['settings'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $this->theme_json['settings'][ $block_selector ] ) ) {
					unset( $this->theme_json['settings'][ $block_selector ] );
					continue;
				}

				// Remove the properties that aren't present in the schema.
				$this->theme_json['settings'][ $block_selector ] = self::remove_keys_not_in_schema(
					$this->theme_json['settings'][ $block_selector ],
					self::SCHEMA['settings']
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $this->theme_json['settings'][ $block_selector ] ) ) {
					unset( $this->theme_json['settings'][ $block_selector ] );
				}
			}
		}

		// Remove the settings & styles subtrees if they're empty after having processed them.
		foreach ( array( 'settings', 'styles' ) as $subtree ) {
			if ( empty( $this->theme_json[ $subtree ] ) ) {
				unset( $this->theme_json[ $subtree ] );
			}
		}

	}

	/**
	 * Given a CSS property name, returns the property it belongs
	 * within the self::PROPERTIES_METADATA map.
	 *
	 * @param string $css_name The CSS property name.
	 *
	 * @return string The property name.
	 */
	private static function to_property( $css_name ) {
		static $to_property;
		if ( null === $to_property ) {
			foreach ( self::PROPERTIES_METADATA as $key => $metadata ) {
				$to_property[ $key ] = $key;
				if ( self::has_properties( $metadata ) ) {
					foreach ( $metadata['properties'] as $property ) {
						$to_property[ $key . '-' . $property ] = $key;
					}
				}
			}
		}
		return $to_property[ $css_name ];
	}

	/**
	 * Returns the metadata for each block.
	 *
	 * Example:
	 *
	 * {
	 *   'root': {
	 *     'selector': ':root'
	 *   },
	 *   'core/heading/h1': {
	 *     'selector': 'h1'
	 *   }
	 * }
	 *
	 * @return array Block metadata.
	 */
	private static function get_blocks_metadata() {
		if ( null !== self::$blocks_metadata ) {
			return self::$blocks_metadata;
		}

		self::$blocks_metadata = array(
			self::ROOT_BLOCK_NAME => array(
				'selector' => self::ROOT_BLOCK_SELECTOR,
			),
			self::ALL_BLOCKS_NAME => array(
				'selector' => self::ALL_BLOCKS_SELECTOR,
			),
		);

		$registry = WP_Block_Type_Registry::get_instance();
		$blocks   = $registry->get_all_registered();
		foreach ( $blocks as $block_name => $block_type ) {
			/*
			 * Assign the selector for the block.
			 *
			 * Some blocks can declare multiple selectors:
			 *
			 * - core/heading represents the H1-H6 HTML elements
			 * - core/list represents the UL and OL HTML elements
			 * - core/group is meant to represent DIV and other HTML elements
			 *
			 * Some other blocks don't provide a selector,
			 * so we generate a class for them based on their name:
			 *
			 * - 'core/group' => '.wp-block-group'
			 * - 'my-custom-library/block-name' => '.wp-block-my-custom-library-block-name'
			 *
			 * Note that, for core blocks, we don't add the `core/` prefix to its class name.
			 * This is for historical reasons, as they come with a class without that infix.
			 *
			 */
			if (
				isset( $block_type->supports['__experimentalSelector'] ) &&
				is_string( $block_type->supports['__experimentalSelector'] )
			) {
				self::$blocks_metadata[ $block_name ] = array(
					'selector' => $block_type->supports['__experimentalSelector'],
				);
			} elseif (
				isset( $block_type->supports['__experimentalSelector'] ) &&
				is_array( $block_type->supports['__experimentalSelector'] )
			) {
				foreach ( $block_type->supports['__experimentalSelector'] as $key => $selector_metadata ) {
					if ( ! isset( $selector_metadata['selector'] ) ) {
						continue;
					}

					self::$blocks_metadata[ $key ] = array(
						'selector' => $selector_metadata['selector'],
					);
				}
			} else {
				self::$blocks_metadata[ $block_name ] = array(
					'selector' => '.wp-block-' . str_replace( '/', '-', str_replace( 'core/', '', $block_name ) ),
				);
			}
		}

		return self::$blocks_metadata;
	}

	/**
	 * Given a tree, removes the keys that are not present in the schema.
	 *
	 * It is recursive and modifies the input in-place.
	 *
	 * @param array $tree Input to process.
	 * @param array $schema Schema to adhere to.
	 *
	 * @return array Returns the modified $tree.
	 */
	private static function remove_keys_not_in_schema( $tree, $schema ) {
		$tree = array_intersect_key( $tree, $schema );

		foreach ( $schema as $key => $data ) {
			if ( is_array( $schema[ $key ] ) && isset( $tree[ $key ] ) ) {
				$tree[ $key ] = self::remove_keys_not_in_schema( $tree[ $key ], $schema[ $key ] );

				if ( empty( $tree[ $key ] ) ) {
					unset( $tree[ $key ] );
				}
			}
		}

		return $tree;
	}

	/**
	 * Given a tree, it creates a flattened one
	 * by merging the keys and binding the leaf values
	 * to the new keys.
	 *
	 * It also transforms camelCase names into kebab-case
	 * and substitutes '/' by '-'.
	 *
	 * This is thought to be useful to generate
	 * CSS Custom Properties from a tree,
	 * although there's nothing in the implementation
	 * of this function that requires that format.
	 *
	 * For example, assuming the given prefix is '--wp'
	 * and the token is '--', for this input tree:
	 *
	 * {
	 *   'some/property': 'value',
	 *   'nestedProperty': {
	 *     'sub-property': 'value'
	 *   }
	 * }
	 *
	 * it'll return this output:
	 *
	 * {
	 *   '--wp--some-property': 'value',
	 *   '--wp--nested-property--sub-property': 'value'
	 * }
	 *
	 * @param array  $tree Input tree to process.
	 * @param string $prefix Prefix to prepend to each variable. '' by default.
	 * @param string $token Token to use between levels. '--' by default.
	 *
	 * @return array The flattened tree.
	 */
	private static function flatten_tree( $tree, $prefix = '', $token = '--' ) {
		$result = array();
		foreach ( $tree as $property => $value ) {
			$new_key = $prefix . str_replace(
				'/',
				'-',
				strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $property ) ) // CamelCase to kebab-case.
			);

			if ( is_array( $value ) ) {
				$new_prefix = $new_key . $token;
				$result     = array_merge(
					$result,
					self::flatten_tree( $value, $new_prefix, $token )
				);
			} else {
				$result[ $new_key ] = $value;
			}
		}
		return $result;
	}

	/**
	 * Returns the style property for the given path.
	 *
	 * It also converts CSS Custom Property stored as
	 * "var:preset|color|secondary" to the form
	 * "--wp--preset--color--secondary".
	 *
	 * @param array $styles Styles subtree.
	 * @param array $path Which property to process.
	 *
	 * @return string Style property value.
	 */
	private static function get_property_value( $styles, $path ) {
		$value = _wp_array_get( $styles, $path, '' );

		if ( '' === $value ) {
			return $value;
		}

		$prefix     = 'var:';
		$prefix_len = strlen( $prefix );
		$token_in   = '|';
		$token_out  = '--';
		if ( 0 === strncmp( $value, $prefix, $prefix_len ) ) {
			$unwrapped_name = str_replace(
				$token_in,
				$token_out,
				substr( $value, $prefix_len )
			);
			$value          = "var(--wp--$unwrapped_name)";
		}

		return $value;
	}

	/**
	 * Whether the metadata contains a key named properties.
	 *
	 * @param array $metadata Description of the style property.
	 *
	 * @return boolean True if properties exists, false otherwise.
	 */
	private static function has_properties( $metadata ) {
		if ( array_key_exists( 'properties', $metadata ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Given a styles array, it extracts the style properties
	 * and adds them to the $declarations array following the format:
	 *
	 * ```php
	 * array(
	 *   'name'  => 'property_name',
	 *   'value' => 'property_value,
	 * )
	 * ```
	 *
	 * @param array $declarations Holds the existing declarations.
	 * @param array $styles       Styles to process.
	 *
	 * @return array Returns the modified $declarations.
	 */
	private static function compute_style_properties( $declarations, $styles ) {
		if ( empty( $styles ) ) {
			return $declarations;
		}

		$properties = array();
		foreach ( self::PROPERTIES_METADATA as $name => $metadata ) {
			// Some properties can be shorthand properties, meaning that
			// they contain multiple values instead of a single one.
			// An example of this is the padding property, see self::SCHEMA.
			if ( self::has_properties( $metadata ) ) {
				foreach ( $metadata['properties'] as $property ) {
					$properties[] = array(
						'name'  => $name . '-' . $property,
						'value' => array_merge( $metadata['value'], array( $property ) ),
					);
				}
			} else {
				$properties[] = array(
					'name'  => $name,
					'value' => $metadata['value'],
				);
			}
		}

		foreach ( $properties as $prop ) {
			$value = self::get_property_value( $styles, $prop['value'] );
			if ( ! empty( $value ) ) {
				$declarations[] = array(
					'name'  => $prop['name'],
					'value' => $value,
				);
			}
		}

		return $declarations;
	}

	/**
	 * Given a settings array, it returns the generated rulesets
	 * for the preset classes.
	 *
	 * @param array  $settings Settings to process.
	 * @param string $selector Selector wrapping the classes.
	 *
	 * @return string The result of processing the presets.
	 */
	private static function compute_preset_classes( $settings, $selector ) {
		if ( self::ROOT_BLOCK_SELECTOR === $selector ) {
			// Classes at the global level do not need any CSS prefixed,
			// and we don't want to increase its specificity.
			$selector = '';
		}

		$stylesheet = '';
		foreach ( self::PRESETS_METADATA as $preset ) {
			$values = _wp_array_get( $settings, $preset['path'], array() );
			foreach ( $values as $value ) {
				foreach ( $preset['classes'] as $class ) {
					$stylesheet .= self::to_ruleset(
						$selector . '.has-' . $value['slug'] . '-' . $class['class_suffix'],
						array(
							array(
								'name'  => $class['property_name'],
								'value' => $value[ $preset['value_key'] ] . ' !important',
							),
						)
					);
				}
			}
		}

		return $stylesheet;
	}

	/**
	 * Given the block settings, it extracts the CSS Custom Properties
	 * for the presets and adds them to the $declarations array
	 * following the format:
	 *
	 * ```php
	 * array(
	 *   'name'  => 'property_name',
	 *   'value' => 'property_value,
	 * )
	 * ```
	 *
	 * @param array $declarations Holds the existing declarations.
	 * @param array $settings Settings to process.
	 *
	 * @return array Returns the modified $declarations.
	 */
	private static function compute_preset_vars( $declarations, $settings ) {
		foreach ( self::PRESETS_METADATA as $preset ) {
			$values = _wp_array_get( $settings, $preset['path'], array() );
			foreach ( $values as $value ) {
				$declarations[] = array(
					'name'  => '--wp--preset--' . $preset['css_var_infix'] . '--' . $value['slug'],
					'value' => $value[ $preset['value_key'] ],
				);
			}
		}

		return $declarations;
	}

	/**
	 * Given an array of settings, it extracts the CSS Custom Properties
	 * for the custom values and adds them to the $declarations
	 * array following the format:
	 *
	 * ```php
	 * array(
	 *   'name'  => 'property_name',
	 *   'value' => 'property_value,
	 * )
	 * ```
	 *
	 * @param array $declarations Holds the existing declarations.
	 * @param array $settings Settings to process.
	 *
	 * @return array Returns the modified $declarations.
	 */
	private static function compute_theme_vars( $declarations, $settings ) {
		$custom_values = _wp_array_get( $settings, array( 'custom' ), array() );
		$css_vars      = self::flatten_tree( $custom_values );
		foreach ( $css_vars as $key => $value ) {
			$declarations[] = array(
				'name'  => '--wp--custom--' . $key,
				'value' => $value,
			);
		}

		return $declarations;
	}

	/**
	 * Given a selector and a declaration list,
	 * creates the corresponding ruleset.
	 *
	 * To help debugging, will add some space
	 * if SCRIPT_DEBUG is defined and true.
	 *
	 * @param string $selector CSS selector.
	 * @param array  $declarations List of declarations.
	 *
	 * @return string CSS ruleset.
	 */
	private static function to_ruleset( $selector, $declarations ) {
		if ( empty( $declarations ) ) {
			return '';
		}
		$ruleset = '';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$declaration_block = array_reduce(
				$declarations,
				function ( $carry, $element ) {
					return $carry .= "\t" . $element['name'] . ': ' . $element['value'] . ";\n"; },
				''
			);
			$ruleset          .= $selector . " {\n" . $declaration_block . "}\n";
		} else {
			$declaration_block = array_reduce(
				$declarations,
				function ( $carry, $element ) {
					return $carry .= $element['name'] . ': ' . $element['value'] . ';'; },
				''
			);
			$ruleset          .= $selector . '{' . $declaration_block . '}';
		}

		return $ruleset;
	}

	/**
	 * Converts each styles section into a list of rulesets
	 * to be appended to the stylesheet.
	 * These rulesets contain all the css variables (custom variables and preset variables).
	 *
	 * See glossary at https://developer.mozilla.org/en-US/docs/Web/CSS/Syntax
	 *
	 * For each section this creates a new ruleset such as:
	 *
	 *   block-selector {
	 *     --wp--preset--category--slug: value;
	 *     --wp--custom--variable: value;
	 *   }
	 *
	 * @return string The new stylesheet.
	 */
	private function get_css_variables() {
		$stylesheet = '';
		if ( ! isset( $this->theme_json['settings'] ) ) {
			return $stylesheet;
		}

		$metadata = self::get_blocks_metadata();
		foreach ( $this->theme_json['settings'] as $block_selector => $settings ) {
			if ( empty( $metadata[ $block_selector ]['selector'] ) ) {
				continue;
			}
			$selector = $metadata[ $block_selector ]['selector'];

			$declarations = self::compute_preset_vars( array(), $settings );
			$declarations = self::compute_theme_vars( $declarations, $settings );

			// Attach the ruleset for style and custom properties.
			$stylesheet .= self::to_ruleset( $selector, $declarations );
		}
		return $stylesheet;
	}

	/**
	 * Converts each style section into a list of rulesets
	 * containing the block styles to be appended to the stylesheet.
	 *
	 * See glossary at https://developer.mozilla.org/en-US/docs/Web/CSS/Syntax
	 *
	 * For each section this creates a new ruleset such as:
	 *
	 *   block-selector {
	 *     style-property-one: value;
	 *   }
	 *
	 * Additionally, it'll also create new rulesets
	 * as classes for each preset value such as:
	 *
	 *   .has-value-color {
	 *     color: value;
	 *   }
	 *
	 *   .has-value-background-color {
	 *     background-color: value;
	 *   }
	 *
	 *   .has-value-font-size {
	 *     font-size: value;
	 *   }
	 *
	 *   .has-value-gradient-background {
	 *     background: value;
	 *   }
	 *
	 *   p.has-value-gradient-background {
	 *     background: value;
	 *   }
	 *
	 * @return string The new stylesheet.
	 */
	private function get_block_styles() {
		$stylesheet = '';
		if ( ! isset( $this->theme_json['styles'] ) && ! isset( $this->theme_json['settings'] ) ) {
			return $stylesheet;
		}

		$metadata     = self::get_blocks_metadata();
		$block_rules  = '';
		$preset_rules = '';
		foreach ( $metadata as $block_selector => $metadata ) {
			if ( empty( $metadata['selector'] ) ) {
				continue;
			}

			$selector = $metadata['selector'];

			$declarations = array();
			if ( isset( $this->theme_json['styles'][ $block_selector ] ) ) {
				$declarations = self::compute_style_properties(
					$declarations,
					$this->theme_json['styles'][ $block_selector ]
				);
			}

			$block_rules .= self::to_ruleset( $selector, $declarations );

			// Attach the rulesets for the classes.
			if ( isset( $this->theme_json['settings'][ $block_selector ] ) ) {
				$preset_rules .= self::compute_preset_classes(
					$this->theme_json['settings'][ $block_selector ],
					$selector
				);
			}
		}

		return $block_rules . $preset_rules;
	}

	/**
	 * Returns the existing settings for each block.
	 *
	 * Example:
	 *
	 * {
	 *   'root': {
	 *     'color': {
	 *       'custom': true
	 *     }
	 *   },
	 *   'core/paragraph': {
	 *     'spacing': {
	 *       'customPadding': true
	 *     }
	 *   }
	 * }
	 *
	 * @return array Settings per block.
	 */
	public function get_settings() {
		if ( ! isset( $this->theme_json['settings'] ) ) {
			return array();
		} else {
			return $this->theme_json['settings'];
		}
	}

	/**
	 * Returns the page templates of the current theme.
	 *
	 * @return array
	 */
	public function get_custom_templates() {
		$custom_templates = array();
		if ( ! isset( $this->theme_json['customTemplates'] ) ) {
			return $custom_templates;
		}

		foreach ( $this->theme_json['customTemplates'] as $item ) {
			if ( isset( $item['name'] ) ) {
				$custom_templates[ $item['name'] ] = array(
					'title'     => isset( $item['title'] ) ? $item['title'] : '',
					'postTypes' => isset( $item['postTypes'] ) ? $item['postTypes'] : array( 'page' ),
				);
			}
		}
		return $custom_templates;
	}

	/**
	 * Returns the template part data of current theme.
	 *
	 * @return array
	 */
	public function get_template_parts() {
		$template_parts = array();
		if ( ! isset( $this->theme_json['templateParts'] ) ) {
			return $template_parts;
		}

		foreach ( $this->theme_json['templateParts'] as $item ) {
			if ( isset( $item['name'] ) ) {
				$template_parts[ $item['name'] ] = array(
					'area' => isset( $item['area'] ) ? $item['area'] : '',
				);
			}
		}
		return $template_parts;
	}

	/**
	 * Returns the stylesheet that results of processing
	 * the theme.json structure this object represents.
	 *
	 * @param string $type Type of stylesheet we want accepts 'all', 'block_styles', and 'css_variables'.
	 * @return string Stylesheet.
	 */
	public function get_stylesheet( $type = 'all' ) {
		switch ( $type ) {
			case 'block_styles':
				return $this->get_block_styles();
			case 'css_variables':
				return $this->get_css_variables();
			default:
				return $this->get_css_variables() . $this->get_block_styles();
		}
	}

	/**
	 * Merge new incoming data.
	 *
	 * @param WP_Theme_JSON $incoming Data to merge.
	 */
	public function merge( $incoming ) {
		$incoming_data    = $incoming->get_raw_data();
		$this->theme_json = array_replace_recursive( $this->theme_json, $incoming_data );

		// The array_replace_recursive algorithm merges at the leaf level.
		// This means that when a leaf value is an array,
		// the incoming array won't replace the existing,
		// but the numeric indexes are used for replacement.
		//
		// These are the cases that have array values at the leaf levels.
		$block_metadata = self::get_blocks_metadata();
		foreach ( $block_metadata as $block_selector => $meta ) {
			// Color presets: palette & gradients.
			if ( isset( $incoming_data['settings'][ $block_selector ]['color']['palette'] ) ) {
				$this->theme_json['settings'][ $block_selector ]['color']['palette'] = $incoming_data['settings'][ $block_selector ]['color']['palette'];
			}
			if ( isset( $incoming_data['settings'][ $block_selector ]['color']['gradients'] ) ) {
				$this->theme_json['settings'][ $block_selector ]['color']['gradients'] = $incoming_data['settings'][ $block_selector ]['color']['gradients'];
			}
			// Spacing: units.
			if ( isset( $incoming_data['settings'][ $block_selector ]['spacing']['units'] ) ) {
				$this->theme_json['settings'][ $block_selector ]['spacing']['units'] = $incoming_data['settings'][ $block_selector ]['spacing']['units'];
			}
			// Typography presets: fontSizes & fontFamilies.
			if ( isset( $incoming_data['settings'][ $block_selector ]['typography']['fontSizes'] ) ) {
				$this->theme_json['settings'][ $block_selector ]['typography']['fontSizes'] = $incoming_data['settings'][ $block_selector ]['typography']['fontSizes'];
			}
			if ( isset( $incoming_data['settings'][ $block_selector ]['typography']['fontFamilies'] ) ) {
				$this->theme_json['settings'][ $block_selector ]['typography']['fontFamilies'] = $incoming_data['settings'][ $block_selector ]['typography']['fontFamilies'];
			}
			// Custom section.
			if ( isset( $incoming_data['settings'][ $block_selector ]['custom'] ) ) {
				$this->theme_json['settings'][ $block_selector ]['custom'] = $incoming_data['settings'][ $block_selector ]['custom'];
			}
		}
	}

	/**
	 * Removes insecure data from theme.json.
	 */
	public function remove_insecure_properties() {
		$blocks_metadata = self::get_blocks_metadata();
		foreach ( $blocks_metadata as $block_selector => $metadata ) {
			$escaped_settings = array();
			$escaped_styles   = array();

			// Style escaping.
			if ( isset( $this->theme_json['styles'][ $block_selector ] ) ) {
				$declarations = self::compute_style_properties( array(), $this->theme_json['styles'][ $block_selector ] );
				foreach ( $declarations as $declaration ) {
					$style_to_validate = $declaration['name'] . ': ' . $declaration['value'];
					if ( esc_html( safecss_filter_attr( $style_to_validate ) ) === $style_to_validate ) {
						$property = self::to_property( $declaration['name'] );
						$path     = self::PROPERTIES_METADATA[ $property ]['value'];
						if ( self::has_properties( self::PROPERTIES_METADATA[ $property ] ) ) {
							$declaration_divided = explode( '-', $declaration['name'] );
							$path[]              = $declaration_divided[1];
						}
						gutenberg_experimental_set(
							$escaped_styles,
							$path,
							_wp_array_get( $this->theme_json['styles'][ $block_selector ], $path, array() )
						);
					}
				}
			}

			// Settings escaping.
			// For now the ony allowed settings are presets.
			if ( isset( $this->theme_json['settings'][ $block_selector ] ) ) {
				foreach ( self::PRESETS_METADATA as $preset_metadata ) {
					$current_preset = _wp_array_get(
						$this->theme_json['settings'][ $block_selector ],
						$preset_metadata['path'],
						null
					);
					if ( null !== $current_preset ) {
						$escaped_preset = array();
						foreach ( $current_preset as $single_preset ) {
							if (
								esc_attr( esc_html( $single_preset['name'] ) ) === $single_preset['name'] &&
								sanitize_html_class( $single_preset['slug'] ) === $single_preset['slug']
							) {
								$value                  = $single_preset[ $preset_metadata['value_key'] ];
								$single_preset_is_valid = null;
								if ( isset( $preset_metadata['classes'] ) && count( $preset_metadata['classes'] ) > 0 ) {
									$single_preset_is_valid = true;
									foreach ( $preset_metadata['classes'] as $class_meta_data ) {
										$property          = $class_meta_data['property_name'];
										$style_to_validate = $property . ': ' . $value;
										if ( esc_html( safecss_filter_attr( $style_to_validate ) ) !== $style_to_validate ) {
											$single_preset_is_valid = false;
											break;
										}
									}
								} else {
									$property               = $preset_metadata['css_var_infix'];
									$style_to_validate      = $property . ': ' . $value;
									$single_preset_is_valid = esc_html( safecss_filter_attr( $style_to_validate ) ) === $style_to_validate;
								}
								if ( $single_preset_is_valid ) {
									$escaped_preset[] = $single_preset;
								}
							}
						}
						if ( ! empty( $escaped_preset ) ) {
							gutenberg_experimental_set( $escaped_settings, $preset_metadata['path'], $escaped_preset );
						}
					}
				}
			}

			if ( empty( $escaped_settings ) ) {
				unset( $this->theme_json['settings'][ $block_selector ] );
			} else {
				$this->theme_json['settings'][ $block_selector ] = $escaped_settings;
			}

			if ( empty( $escaped_styles ) ) {
				unset( $this->theme_json['styles'][ $block_selector ] );
			} else {
				$this->theme_json['styles'][ $block_selector ] = $escaped_styles;
			}
		}
	}

	/**
	 * Returns the raw data.
	 *
	 * @return array Raw data.
	 */
	public function get_raw_data() {
		return $this->theme_json;
	}

}
