<?php
/**
 * Tests the StylesLoader class.
 *
 * @package GoDaddy_Styles
 */

use GoDaddy\Styles\StylesLoader;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the StylesLoader class.
 */
class StylesLoaderTest extends WP_UnitTestCase {
	/**
	 * The StylesLoader instance.
	 *
	 * @var \GoDaddy\Styles\StylesLoader
	 */
	private $styles_loader;

	/**
	 * The base_path of vfsStream.
	 *
	 * @var string
	 */
	private $plugin_base_path;

	/**
	 * The base_url of vfsStream.
	 *
	 * @var string
	 */
	private $plugin_base_url;

	public function set_up() {
        parent::set_up();

		$this->styles_loader = new StylesLoaderStub();

		// Create a virtual filesyste to test against.
		vfsStream::setup( 'root', null, array(
			'wp-content' => array(
				'plugins' => array(
					'styles' => array(
						'build' => array(
							'latest.css' => '',
							'wp' => array(
								'5.8.0.css' => '',
								'5.9.0.css' => '',
								'5.9.1.css' => '',
								'5.9.2.css' => '',
							),
						),
					),
				),
			),
		) );

		$__DIR__ = 'wp-content/plugins/styles/';

		$this->plugin_base_path = vfsStream::url( 'root' ) . '/' . $__DIR__;
		$this->plugin_base_url = vfsStream::url( 'root' ) . '/' . $__DIR__;
    }

	public function tear_down() {
		parent::tear_down();

		$this->styles_loader = null;

		unset( $GLOBALS['wp_styles'] );
    }

	/**
	 * TESTS:
	 * - If style handle has not already been enqueued, enqueue it.
	 * - If the current plugin is a mu-plugin, override any enqueued handle.
	 * - If style handle has been enqueued:
	 * 		- do nothing if the src is from the mu-plugin dir.
	 * 		- enqueue only if we're enqueuing a more recent version.
	 */

    public function test_has_registered_returns_false_if_missing() {
		$this->assertEmpty( wp_styles()->registered[ $this->styles_loader::HANDLE ] );
		$this->assertFalse( $this->styles_loader->hasRegistered() );
    }

	public function test_has_registered_returns_true_if_registered() {
		wp_enqueue_style( $this->styles_loader::HANDLE, 'styles.css', array(), '1.0.0' );

		$this->assertNotEmpty( wp_styles()->registered[ $this->styles_loader::HANDLE ] );
		$this->assertTrue( $this->styles_loader->hasRegistered() );
    }

	public function test_get_registered_returns_array_or_false() {
		$this->assertFalse( $this->styles_loader->getRegistered() );

		wp_enqueue_style( $this->styles_loader::HANDLE, 'styles.css', array(), '1.0.0' );
		$this->assertInstanceOf( _WP_Dependency::class, $this->styles_loader->getRegistered() );
	}

	public function test_is_must_use_returns_true_for_mu_plugin_path() {
		wp_enqueue_style( $this->styles_loader::HANDLE, 'styles.css', array(), '1.0.0' );

		$this->assertFalse( $this->styles_loader->isMustUse() );

		wp_deregister_style( $this->styles_loader::HANDLE );
		wp_enqueue_style( $this->styles_loader::HANDLE, 'mu-plugins/styles.css', array(), '1.0.0' );

		$this->assertTrue( $this->styles_loader->isMustUse() );
	}

	public function test_boot_hooks_exist() {
		$callback = array( $this->styles_loader, 'enqueue' );

		$this->assertFalse( has_action( 'admin_enqueue_scripts', $callback ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', $callback ) );

		$this->styles_loader->boot();

		// Assert "not false" because has_action returns the priority of the hook if the callback exists.
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', $callback ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', $callback ) );
	}

	public function test_skips_enqueue_when_older_version() {
		$this->styles_loader->boot();
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			StylesLoaderStub::VERSION,
			$this->styles_loader->getRegisteredVersion()
		);

		$older_styles_loader = new OlderStylesLoaderStub;
		$older_styles_loader->boot();
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			StylesLoaderStub::VERSION,
			$older_styles_loader->getRegisteredVersion()
		);
	}

	public function test_enqueue_when_newer_version() {
		$this->styles_loader->boot();
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			StylesLoaderStub::VERSION,
			$this->styles_loader->getRegisteredVersion()
		);

		$newer_styles_loader = new NewerStylesLoaderStub;
		$newer_styles_loader->boot();
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			NewerStylesLoaderStub::VERSION,
			$newer_styles_loader->getRegisteredVersion()
		);
    }

	public function test_skips_enqueue_when_mu_plugin_registered() {
		$must_use_styles_loader = new MustUseStylesLoaderStub;
		$must_use_styles_loader->boot();
		$must_use_styles_loader->setBasePath( $this->plugin_base_path );
		$must_use_styles_loader->setBaseUrl( $this->plugin_base_url );
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			MustUseStylesLoaderStub::VERSION,
			$must_use_styles_loader->getRegisteredVersion()
		);

		$newer_styles_loader = new NewerStylesLoaderStub;
		$newer_styles_loader->boot();
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			MustUseStylesLoaderStub::VERSION,
			$newer_styles_loader->getRegisteredVersion()
		);
    }

	public function test_enqueues_latest_styles_by_default() {
		$this->styles_loader->boot();
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			'build/latest.css',
			$this->styles_loader->getRegistered()->src
		);
	}

	public function test_enqueues_wp_minor_version_styles() {
		global $wp_version;
		$wp_version = '5.9.1';

		$this->styles_loader->boot();
		$this->styles_loader->setBasePath( $this->plugin_base_path );
		$this->styles_loader->setBaseUrl( $this->plugin_base_url );
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			'build/wp/5.9.1.css',
			str_replace( $this->plugin_base_path, '', $this->styles_loader->getRegistered()->src )
		);
	}

	public function test_enqueues_last_minor_version_stylesheet_for_wp_version() {
		global $wp_version;
		$wp_version = '5.8.3';

		$this->styles_loader->boot();
		$this->styles_loader->setBasePath( $this->plugin_base_path );
		$this->styles_loader->setBaseUrl( $this->plugin_base_url );
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			'build/wp/5.8.0.css',
			str_replace( $this->plugin_base_path, '', $this->styles_loader->getRegistered()->src )
		);
	}

	public function test_enqueues_latest_stylesheet_for_new_major_wp_verion() {
		global $wp_version;
		$wp_version = '6.0.0';

		$this->styles_loader->boot();
		$this->styles_loader->setBasePath( $this->plugin_base_path );
		$this->styles_loader->setBaseUrl( $this->plugin_base_url );
		do_action( 'wp_enqueue_scripts' );

		$this->assertEquals(
			'build/latest.css',
			str_replace( $this->plugin_base_path, '', $this->styles_loader->getRegistered()->src )
		);
	}
}

class StylesLoaderStub extends StylesLoader {
	const VERSION = '1.0.0';
	const HANDLE = 'godaddy-styles-testing';
}
class OlderStylesLoaderStub extends StylesLoader {
	const VERSION = '0.1.0';
	const HANDLE = 'godaddy-styles-testing';
}
class NewerStylesLoaderStub extends StylesLoader {
	const VERSION = '2.0.0';
	const HANDLE = 'godaddy-styles-testing';
}
class MustUseStylesLoaderStub extends StylesLoader {
	const VERSION = '1.0.0';
	const HANDLE = 'godaddy-styles-testing';

	public function enqueue() {
		$this->base_path = str_replace( 'plugins/', 'mu-plugins/', $this->base_path );
		$this->base_url = str_replace( 'plugins/', 'mu-plugins/', $this->base_url );
		parent::enqueue();
	}
}