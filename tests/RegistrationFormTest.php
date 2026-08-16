<?php

// tests/RegistrationFormTest.php

use PHPUnit\Framework\TestCase;
use Luxid\Nova\ComponentManager;
use Luxid\Nova\Validation\Validator;

class RegistrationFormTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    // Clear component registry before each test
    ComponentManager::clear();

    // Start session for state management
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    // `require`, not `require_once`: the registry is cleared before every test,
    // so the definitions have to be re-registered each time.
    require __DIR__ . '/../components.php';
  }

  protected function tearDown(): void
  {
    // Clean up session
    $_SESSION = [];
    parent::tearDown();
  }

  /**
   * Test that the component can be registered
   */
  public function testComponentRegistration(): void
  {
    $this->assertTrue(ComponentManager::has('registration-form'));
    $this->assertNotNull(ComponentManager::resolve('registration-form'));
  }

  /**
   * Test component initial state
   */
  public function testInitialState(): void
  {
    $component = ComponentManager::make('registration-form');
    $state = $component->getState();

    $this->assertEquals('', $state['name']);
    $this->assertEquals('', $state['email']);
    $this->assertEquals('', $state['password']);
    $this->assertEquals('', $state['password_confirmation']);
    $this->assertEquals([], $state['errors']);
    $this->assertFalse($state['success']);
  }

  /**
   * Test successful registration
   */
  public function testSuccessfulRegistration(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];

    $result = $component->callAction('register', $testData);

    $state = $component->getState();

    $this->assertEquals('John Doe', $state['name']);
    $this->assertEquals('john@example.com', $state['email']);
    $this->assertTrue($state['success']);
    $this->assertEmpty($state['errors']);
    $this->assertStringContainsString('Registration Successful', $result);
  }

  /**
   * Test registration with missing fields
   */
  public function testRegistrationWithMissingFields(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => '',
      'email' => '',
      'password' => '',
      'password_confirmation' => ''
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    $this->assertFalse($state['success']);
    $this->assertArrayHasKey('name', $state['errors']);
    $this->assertArrayHasKey('email', $state['errors']);
    $this->assertArrayHasKey('password', $state['errors']);
    $this->assertStringContainsString('required', $state['errors']['name'][0]);
  }

  /**
   * Test registration with invalid email
   */
  public function testRegistrationWithInvalidEmail(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => 'John Doe',
      'email' => 'not-an-email',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    $this->assertFalse($state['success']);
    $this->assertArrayHasKey('email', $state['errors']);
    $this->assertStringContainsString('valid email', strtolower($state['errors']['email'][0]));
  }

  /**
   * Test registration with password too short
   */
  public function testRegistrationWithShortPassword(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => 'short',
      'password_confirmation' => 'short'
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    $this->assertFalse($state['success']);
    $this->assertArrayHasKey('password', $state['errors']);
    $this->assertStringContainsString('8 characters', $state['errors']['password'][0]);
  }

  /**
   * Test registration with password confirmation mismatch
   */
  public function testRegistrationWithPasswordMismatch(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'password' => 'password123',
      'password_confirmation' => 'different123'
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    $this->assertFalse($state['success']);
    $this->assertArrayHasKey('password', $state['errors']);
    $this->assertStringContainsString('match', strtolower($state['errors']['password'][0]));
  }

  /**
   * Test registration with name too short
   */
  public function testRegistrationWithShortName(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => 'A',
      'email' => 'john@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    $this->assertFalse($state['success']);
    $this->assertArrayHasKey('name', $state['errors']);
    $this->assertStringContainsString('2 characters', $state['errors']['name'][0]);
  }

  /**
   * Test registration with name too long
   */
  public function testRegistrationWithLongName(): void
  {
    $component = ComponentManager::make('registration-form');

    $longName = str_repeat('a', 51);
    $testData = [
      'name' => $longName,
      'email' => 'john@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    $this->assertFalse($state['success']);
    $this->assertArrayHasKey('name', $state['errors']);
    $this->assertStringContainsString('50 characters', $state['errors']['name'][0]);
  }

  /**
   * Test that component renders HTML
   */
  public function testComponentRenders(): void
  {
    $component = ComponentManager::make('registration-form');
    $html = $component->render();

    $this->assertStringContainsString('<form data-nova-submit="register"', $html);
    $this->assertStringContainsString('Create an Account', $html);
    $this->assertStringContainsString('Register', $html);
    $this->assertStringContainsString('data-nova-component', $html);
  }

  /**
   * Test that errors are displayed in rendered HTML
   */
  public function testErrorsDisplayedInRender(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => '',
      'email' => 'invalid',
      'password' => 'short',
      'password_confirmation' => 'different'
    ];

    $component->callAction('register', $testData);
    $html = $component->render();

    $this->assertStringContainsString('error', $html);
    $this->assertMatchesRegularExpression('/Name.*required/i', $html);
  }

  /**
   * Test multiple validation errors at once
   */
  public function testMultipleValidationErrors(): void
  {
    $component = ComponentManager::make('registration-form');

    $testData = [
      'name' => 'A',
      'email' => 'invalid',
      'password' => 'short',
      'password_confirmation' => 'different'
    ];

    $component->callAction('register', $testData);
    $state = $component->getState();

    // `confirmed` reports against the field that declared it, so the mismatch
    // lands on `password` alongside its length failure rather than on the
    // confirmation input.
    $this->assertCount(3, $state['errors']);
    $this->assertArrayHasKey('name', $state['errors']);
    $this->assertArrayHasKey('email', $state['errors']);
    $this->assertArrayHasKey('password', $state['errors']);
  }

  /**
   * Test that action can be called multiple times
   */
  public function testMultipleActionCalls(): void
  {
    $component = ComponentManager::make('registration-form');

    // First registration attempt (invalid)
    $invalidData = [
      'name' => '',
      'email' => 'invalid',
      'password' => 'short',
      'password_confirmation' => 'different'
    ];
    $component->callAction('register', $invalidData);
    $state = $component->getState();
    $this->assertFalse($state['success']);
    $this->assertNotEmpty($state['errors']);

    // Second registration attempt (valid)
    $validData = [
      'name' => 'Jane Smith',
      'email' => 'jane@example.com',
      'password' => 'securepass123',
      'password_confirmation' => 'securepass123'
    ];
    $component->callAction('register', $validData);
    $state = $component->getState();

    $this->assertTrue($state['success']);
    $this->assertEquals('Jane Smith', $state['name']);
    $this->assertEquals('jane@example.com', $state['email']);
    $this->assertEmpty($state['errors']);
  }

  /**
   * Test that component state persists across instances with same ID
   */
  public function testStatePersistence(): void
  {
    $instanceId = 'test-instance';

    // First instance - register a user
    $component1 = ComponentManager::make('registration-form', $instanceId);
    $testData = [
      'name' => 'Persistent User',
      'email' => 'persistent@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];
    $component1->callAction('register', $testData);

    // Second instance with same ID - should have same state
    $component2 = ComponentManager::make('registration-form', $instanceId);
    $state = $component2->getState();

    $this->assertTrue($state['success']);
    $this->assertEquals('Persistent User', $state['name']);
    $this->assertEquals('persistent@example.com', $state['email']);
  }

  /**
   * Test that different instances have independent state
   */
  public function testIndependentInstances(): void
  {
    $component1 = ComponentManager::make('registration-form', 'instance1');
    $component2 = ComponentManager::make('registration-form', 'instance2');

    $testData1 = [
      'name' => 'User One',
      'email' => 'user1@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];
    $component1->callAction('register', $testData1);

    $testData2 = [
      'name' => 'User Two',
      'email' => 'user2@example.com',
      'password' => 'password123',
      'password_confirmation' => 'password123'
    ];
    $component2->callAction('register', $testData2);

    $state1 = $component1->getState();
    $state2 = $component2->getState();

    $this->assertEquals('User One', $state1['name']);
    $this->assertEquals('User Two', $state2['name']);
    $this->assertNotEquals($state1['name'], $state2['name']);
  }

  /**
   * Test validator rules directly
   */
  public function testValidatorRules(): void
  {
    $validator = new Validator([
      'name' => 'John',
      'email' => 'john@example.com',
      'password' => 'password123'
    ], [
      'name' => 'required|min:2',
      'email' => 'required|email',
      'password' => 'required|min:8'
    ]);

    $isValid = $validator->validate();

    $this->assertTrue($isValid);
    $this->assertEmpty($validator->getErrors());
  }

  /**
   * Test validator with invalid data
   */
  public function testValidatorWithInvalidData(): void
  {
    $validator = new Validator([
      'name' => '',
      'email' => 'invalid',
      'password' => 'short'
    ], [
      'name' => 'required',
      'email' => 'required|email',
      'password' => 'required|min:8'
    ]);

    $isValid = $validator->validate();

    $this->assertFalse($isValid);
    $this->assertNotEmpty($validator->getErrors());
    $this->assertArrayHasKey('name', $validator->getErrors());
    $this->assertArrayHasKey('email', $validator->getErrors());
    $this->assertArrayHasKey('password', $validator->getErrors());
  }

  /**
   * Test custom error messages
   */
  public function testCustomErrorMessages(): void
  {
    $validator = new Validator(
      ['name' => ''],
      ['name' => 'required'],
      ['name.required' => 'Please enter your name']
    );

    $validator->validate();
    $errors = $validator->getErrors();

    $this->assertEquals('Please enter your name', $errors['name'][0]);
  }
}
