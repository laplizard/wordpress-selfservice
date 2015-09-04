<?php
class SelfServiceTest 
extends PHPUnit_Framework_TestCase 
{
	public function test_noop() {
		$manager = new WPSSManager();
	}
	/**
	 * add website
	 */
	public function test_add_website() {
		$site = array(
			'post_type' => 'wpss_site',
			'post_content' => 'test description',
			'post_title' => 'test title',
			'post_author' => 1, // ??
			'post_status' => 'publish',
		);
		$id = wp_insert_post( $site );
		$this->assertNotEquals( $id, 0 );
		return $id;
	}
	/** 
	 * check pillar for test
	 * @depends test_add_website
	 */
	public function test_pillar( $id ) {
		$manager = new WPSSManager();
		$minion_id = '*'; // TODO fix me.
		$pillar = $manager->get_pillar( $minion_id );
		$this->assertArraySubset(
			array(
				'apache' => array(
					'sites' => array(
						'127.0.0.1' => array(
							'locations' => array(
								'/test-title' => array(
									'DocumentRoot' => '/srv/selfservice/'.$id,
									'available' => true,

								),
							),
						),
					),
				),
			),
			$pillar
		);
		$this->assertArraySubset(
			array(
				'selfservice' => array(
					'sites' => array(
						'/srv/selfservice/'.$id => array(
							'id' => $id,
							'type' => 'wordpress',
							'title' => 'test title',
							'description' => 'test description',
							'url' => 'http://127.0.0.1:8080/test-title',
						),
					),
				),
			),
			$pillar
		);
		return $id;
	}
	/** 
 	 * delete test item
	 * @depends test_add_website
	 */
	public function test_delete( $id ) {
		$res = wp_delete_post( $id, true );
		// String equality?!
		$this->assertTrue( $res !== false );
	}
}
