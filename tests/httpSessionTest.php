<?php

use Cocoon\Http\Facades\Session;
use PHPUnit\Framework\TestCase;

class httpSessionTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		
		// Nettoyer toute session existante
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_destroy();
			session_write_close();
			session_unset();
		}
		
		// Réinitialiser les données de session
		$_SESSION = [];
		
		// Démarrer une nouvelle session
		Session::start();
		Session::clear();
	}

	public function testIdSessionTrue(): void
	{
		$this->assertTrue(Session::isSession());
	}

	public function testSetAndGetSession(): void
	{
		// Test avec une valeur simple
		Session::set('name', 'John Doe');
		$this->assertEquals('John Doe', Session::get('name'));

		// Test avec un tableau
		$data = ['email' => 'john@example.com', 'age' => 30];
		Session::set('user', $data);
		$this->assertEquals($data, Session::get('user'));

		// Test avec une valeur par défaut
		$this->assertEquals('default', Session::get('inexistant', 'default'));
	}

	public function testHasSession(): void
	{
		Session::set('test_key', 'test_value');
		$this->assertTrue(Session::has('test_key'));
		$this->assertFalse(Session::has('inexistant_key'));
	}

	public function testDeleteSession(): void
	{
		Session::set('to_delete', 'value');
		$this->assertTrue(Session::has('to_delete'));
		
		Session::delete('to_delete');
		$this->assertFalse(Session::has('to_delete'));
	}

	public function testFlashMessages(): void
	{
		// Test flash message simple
		Session::flash('success', 'Opération réussie');
		$this->assertEquals('Opération réussie', Session::getFlash('success'));
		
		// Vérifier que le message disparaît après lecture
		$this->assertNull(Session::getFlash('success'));

		// Test multiple flash messages
		Session::flash('errors', ['Erreur 1', 'Erreur 2']);
		$this->assertEquals(['Erreur 1', 'Erreur 2'], Session::getFlash('errors'));
	}

	public function testClearSession(): void
	{
		Session::set('key1', 'value1');
		Session::set('key2', 'value2');
		
		$this->assertTrue(Session::has('key1'));
		$this->assertTrue(Session::has('key2'));
		
		Session::clear();
		
		$this->assertFalse(Session::has('key1'));
		$this->assertFalse(Session::has('key2'));
	}

	public function testAllSession(): void
	{
		Session::clear();
		
		$testData = [
			'user' => ['name' => 'John', 'email' => 'john@example.com'],
			'settings' => ['theme' => 'dark', 'language' => 'fr']
		];
		
		foreach ($testData as $key => $value) {
			Session::set($key, $value);
		}
		
		$allData = Session::all();
		
		foreach ($testData as $key => $value) {
			$this->assertArrayHasKey($key, $allData);
			$this->assertEquals($value, $allData[$key]);
		}
	}

	public function testPutMultipleValues(): void
	{
		$data = [
			'name' => 'John Doe',
			'email' => 'john@example.com',
			'preferences' => ['theme' => 'dark']
		];
		
		Session::put($data);
		
		foreach ($data as $key => $value) {
			$this->assertEquals($value, Session::get($key));
		}
	}

	public function testRemoveMultipleKeys(): void
	{
		// Préparer plusieurs clés
		Session::set('key1', 'value1');
		Session::set('key2', 'value2');
		Session::set('key3', 'value3');
		
		// Supprimer plusieurs clés
		Session::remove(['key1', 'key2']);
		
		// Vérifier que les clés sont supprimées
		$this->assertFalse(Session::has('key1'));
		$this->assertFalse(Session::has('key2'));
		$this->assertTrue(Session::has('key3'));
	}

	public function testSessionRegenerate(): void
	{
		// S'assurer que la session est active
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		
		// Enregistrer des données
		Session::set('test', 'value');
		$oldId = session_id();
		
		// Régénérer l'ID sans détruire
		@session_regenerate_id(false);
		$newId = session_id();
		
		// Vérifier que l'ID a changé mais que les données persistent
		$this->assertNotEquals($oldId, $newId);
		$this->assertEquals('value', Session::get('test'));
		
		// Régénérer l'ID avec destruction
		@session_regenerate_id(true);
		$this->assertNotEquals($newId, session_id());
	}

	public function testSetAndGetMultipleValues(): void
	{
		$values = [
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => ['nested' => 'value']
		];
		
		Session::setMultiple($values);
		
		$retrieved = Session::getMultiple(array_keys($values));
		$this->assertEquals($values, $retrieved);
		
		// Test avec des clés inexistantes
		$result = Session::getMultiple(['key1', 'inexistant'], 'default');
		$this->assertEquals('value1', $result['key1']);
		$this->assertEquals('default', $result['inexistant']);
	}

	public function testHasMultipleKeys(): void
	{
		Session::setMultiple([
			'exists1' => 'value1',
			'exists2' => 'value2'
		]);
		
		$this->assertTrue(Session::hasMultiple(['exists1', 'exists2']));
		$this->assertFalse(Session::hasMultiple(['exists1', 'not_exists']));
	}

	public function testFlashDataPersistence(): void
	{
		// Test que les données flash persistent jusqu'à leur lecture
		Session::flash('test1', 'value1');
		Session::flash('test2', 'value2');
		
		// Simuler une nouvelle requête
		Session::start();
		
		$this->assertEquals('value1', Session::getFlash('test1'));
		$this->assertEquals('value2', Session::getFlash('test2'));
		
		// Vérifier que les données sont supprimées après lecture
		$this->assertNull(Session::getFlash('test1'));
		$this->assertNull(Session::getFlash('test2'));
	}

	public function testMetaData(): void
	{
		// Test d'enregistrement de métadonnées
		Session::setMeta('last_activity', time());
		Session::setMeta('user_agent', 'PHPUnit');
		
		$this->assertTrue(Session::hasMeta('last_activity'));
		$this->assertEquals('PHPUnit', Session::getMeta('user_agent'));
		
		// Test de suppression de métadonnées
		Session::deleteMeta('user_agent');
		$this->assertFalse(Session::hasMeta('user_agent'));
		
		// Test de valeur par défaut pour métadonnées
		$this->assertEquals('default', Session::getMeta('inexistant', 'default'));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testSetUpSessionHandler(): void
	{
		// Détruire complètement la session existante
		if (session_status() === PHP_SESSION_ACTIVE) {
			@session_destroy();
		}
		
		// Fermer la session et nettoyer
		@session_write_close();
		@session_unset();
		$_SESSION = [];
		
		// Réinitialiser les cookies de session
		if (isset($_COOKIE[session_name()])) {
			unset($_COOKIE[session_name()]);
		}

		// Vérifier que la session est bien nettoyée
		$this->assertEquals(PHP_SESSION_NONE, session_status());
		$this->assertEmpty($_SESSION);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testSessionHandler(): void
	{
		// Désactiver le démarrage automatique des sessions
		ini_set('session.auto_start', 0);
		
		// S'assurer qu'aucune session n'est active
		if (session_status() === PHP_SESSION_ACTIVE) {
			@session_write_close();
			@session_unset();
			@session_destroy();
		}
		
		// Réinitialiser les données de session
		$_SESSION = [];
		
		// Réinitialiser les cookies de session
		if (isset($_COOKIE[session_name()])) {
			unset($_COOKIE[session_name()]);
		}
		
		// Réinitialiser le gestionnaire de session par défaut
		ini_set('session.save_handler', 'files');
		
		// Réinitialiser l'instance de HttpSession
		$reflection = new \ReflectionClass(\Cocoon\Http\HttpSession::class);
		
		// Réinitialiser la propriété instance
		$instanceProperty = $reflection->getProperty('instance');
		$instanceProperty->setAccessible(true);
		$instanceProperty->setValue(null, null);
		
		// Réinitialiser la propriété isSession
		$instance = Session::getInstance();
		$isSessionProperty = $reflection->getProperty('isSession');
		$isSessionProperty->setAccessible(true);
		$isSessionProperty->setValue($instance, false);
		
		// Configurer le gestionnaire avant toute chose
		$result = Session::setHandler('files', [
			'save_path' => sys_get_temp_dir()
		]);
		$this->assertTrue($result);
		
		// Maintenant on peut démarrer la session
		Session::start();
		
		// Tester le gestionnaire
		Session::set('handler_test', 'value');
		$this->assertEquals('value', Session::get('handler_test'));
	}

	protected function tearDown(): void
	{
		// Nettoyer la session
		if (session_status() === PHP_SESSION_ACTIVE) {
			Session::clear();
			session_destroy();
			session_write_close();
			session_unset();
		}
		
		// Réinitialiser les données de session
		$_SESSION = [];
		
		parent::tearDown();
	}
}