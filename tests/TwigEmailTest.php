<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigContainer;
use Azt3k\SS\Twig\TwigEmail;
use Azt3k\SS\Twig\TwigViewableData;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\ModelData;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\TextPart;

class TwigEmailTest extends SapphireTest
{
    protected $usesDatabase = false;

    private array $originalConfig;

    protected function setUp(): void
    {
        parent::setUp();
        Config::modify()->set(TwigEmail::class, 'admin_email', 'test@example.com');
        Director::config()->set('alternate_base_url', 'http://localhost/');
        $this->originalConfig = TwigContainer::getConfig();

        // Add test fixtures directory for template rendering
        TwigContainer::extendConfig([
            'twig.module_template_paths' => [
                dirname(__DIR__) . '/tests/fixtures',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        TwigContainer::setConfig($this->originalConfig);
        parent::tearDown();
    }

    public function testConstructorSetsFrom(): void
    {
        // GIVEN a sender email address
        // WHEN we construct a TwigEmail with a from address
        $email = new TwigEmail('sender@example.com');

        // THEN the from address should be set
        $from = $email->getFrom();
        $this->assertNotEmpty($from);
    }

    public function testConstructorSetsTo(): void
    {
        // GIVEN a recipient email address
        // WHEN we construct a TwigEmail with a to address
        $email = new TwigEmail('', 'recipient@example.com');

        // THEN the to address should be set
        $to = $email->getTo();
        $this->assertNotEmpty($to);
    }

    public function testConstructorSetsSubject(): void
    {
        // GIVEN a subject line
        // WHEN we construct a TwigEmail with a subject
        $email = new TwigEmail('', '', 'Test Subject');

        // THEN the subject should be set
        $this->assertSame('Test Subject', $email->getSubject());
    }

    public function testConstructorSetsCC(): void
    {
        // GIVEN a CC email address
        // WHEN we construct a TwigEmail with a CC recipient
        $email = new TwigEmail('sender@example.com', '', '', '', 'cc@example.com');

        // THEN the CC address should be set
        $cc = $email->getCc();
        $this->assertNotEmpty($cc);
    }

    public function testConstructorSetsBCC(): void
    {
        // GIVEN a BCC email address
        // WHEN we construct a TwigEmail with a BCC recipient
        $email = new TwigEmail('sender@example.com', '', '', '', '', 'bcc@example.com');

        // THEN the BCC address should be set
        $bcc = $email->getBcc();
        $this->assertNotEmpty($bcc);
    }

    public function testConstructorSetsReturnPath(): void
    {
        // GIVEN a return path email address
        // WHEN we construct a TwigEmail with a return path
        $email = new TwigEmail('sender@example.com', '', '', '', '', '', 'return@example.com');

        // THEN the return path should be set to that address
        $returnPath = $email->getReturnPath();
        $this->assertSame('return@example.com', $returnPath->getAddress());
    }

    public function testSetBodyWithStringCallsHtml(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we call setBody with an HTML string
        $email->setBody('<p>Test body</p>');

        // THEN it should be stored as the HTML body (not as a Symfony AbstractPart)
        $this->assertSame('<p>Test body</p>', $email->getHtmlBody());
    }

    public function testSetBodyWithNullDoesNotThrow(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we call setBody with null
        $result = $email->setBody(null);

        // THEN it should return the email instance without error
        $this->assertInstanceOf(TwigEmail::class, $result);
    }

    public function testSetBodyWithAbstractPart(): void
    {
        // GIVEN a TwigEmail instance and a Symfony TextPart
        $email = new TwigEmail('sender@example.com');
        $part = new TextPart('Hello plain');

        // WHEN we call setBody with the AbstractPart
        $result = $email->setBody($part);

        // THEN it should delegate to Symfony's Message::setBody()
        $this->assertInstanceOf(TwigEmail::class, $result);
    }

    public function testGetDataReturnsModelData(): void
    {
        // GIVEN a TwigEmail with no custom data set
        $email = new TwigEmail('sender@example.com');

        // WHEN we call getData()
        $data = $email->getData();

        // THEN it should return a ModelData instance with default properties
        $this->assertInstanceOf(ModelData::class, $data);
    }

    public function testGetDataIncludesIsEmail(): void
    {
        // GIVEN a TwigEmail with no custom data
        $email = new TwigEmail('sender@example.com');

        // WHEN we call getData()
        $data = $email->getData();

        // THEN IsEmail should be true (used by templates to detect email context)
        $this->assertTrue($data->IsEmail);
    }

    public function testGetDataIncludesBaseURL(): void
    {
        // GIVEN a TwigEmail with alternate_base_url configured
        $email = new TwigEmail('sender@example.com');

        // WHEN we call getData()
        $data = $email->getData();

        // THEN BaseURL should be populated from Director::absoluteBaseURL()
        $this->assertNotEmpty($data->BaseURL);
    }

    public function testGetDataDoesNotOverrideExistingIsEmail(): void
    {
        // GIVEN a TwigEmail where IsEmail has been explicitly set to false
        $email = new TwigEmail('sender@example.com');
        $email->addData('IsEmail', false);

        // WHEN we call getData()
        $data = $email->getData();

        // THEN the explicit value should be preserved, not overridden by the default
        $this->assertFalse($data->IsEmail);
    }

    public function testSetDataWithArray(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we call setData with an array
        $result = $email->setData(['Foo' => 'bar']);

        // THEN the data should be accessible via getData() and method chaining works
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertSame('bar', $data->Foo);
    }

    public function testSetDataWithModelData(): void
    {
        // GIVEN a TwigEmail instance and an ArrayData object
        $email = new TwigEmail('sender@example.com');
        $modelData = ArrayData::create(['Key' => 'value']);

        // WHEN we call setData with the ModelData object
        $result = $email->setData($modelData);

        // THEN the data should be set as the failover and accessible via getData()
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertSame('value', $data->Key);
    }

    public function testAddDataWithKeyValue(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we add a single key-value pair
        $result = $email->addData('TestKey', 'TestValue');

        // THEN the key should be accessible in getData()
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertSame('TestValue', $data->TestKey);
    }

    public function testAddDataWithArray(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we add multiple key-value pairs via array
        $email->addData(['A' => 1, 'B' => 2]);

        // THEN both keys should be accessible
        $data = $email->getData();
        $this->assertSame(1, $data->A);
        $this->assertSame(2, $data->B);
    }

    public function testRemoveData(): void
    {
        // GIVEN a TwigEmail with a data key set
        $email = new TwigEmail('sender@example.com');
        $email->addData('RemoveMe', 'value');

        // WHEN we remove that key
        $result = $email->removeData('RemoveMe');

        // THEN the key should be null in getData()
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertNull($data->RemoveMe);
    }

    public function testSetHTMLTemplate(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we set an HTML template name
        $email->setHTMLTemplate('MyTemplate');

        // THEN getHTMLTemplate should return that exact name
        $this->assertSame('MyTemplate', $email->getHTMLTemplate());
    }

    public function testSetHTMLTemplateStripsSSExtension(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we set a template name with .ss extension
        $email->setHTMLTemplate('MyTemplate.ss');

        // THEN the .ss extension should be stripped (Twig doesn't use .ss)
        $this->assertSame('MyTemplate', $email->getHTMLTemplate());
    }

    public function testSetPlainTemplate(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we set a plain text template
        $email->setPlainTemplate('PlainTemplate');

        // THEN getPlainTemplate should return that name
        $this->assertSame('PlainTemplate', $email->getPlainTemplate());
    }

    public function testSetPlainTemplateStripsSSExtension(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we set a plain template with .ss extension
        $email->setPlainTemplate('PlainTemplate.ss');

        // THEN the .ss extension should be stripped
        $this->assertSame('PlainTemplate', $email->getPlainTemplate());
    }

    public function testGetPlainTemplateDefaultsToEmpty(): void
    {
        // GIVEN a TwigEmail with no plain template set
        $email = new TwigEmail('sender@example.com');

        // WHEN we read the plain template
        // THEN it should default to empty string
        $this->assertSame('', $email->getPlainTemplate());
    }

    public function testGetHTMLTemplateDefaultUsesSSViewer(): void
    {
        // GIVEN a TwigEmail with no explicit HTML template set
        $email = new TwigEmail('sender@example.com');

        // WHEN we call getHTMLTemplate (no template was set via setHTMLTemplate)
        $template = $email->getHTMLTemplate();

        // THEN it should return an array of template candidates from SSViewer::get_templates_by_class()
        // (SS6 API change: ThemeResourceLoader::findTemplate() was removed, so the raw array is returned)
        $this->assertIsArray($template);
        $this->assertNotEmpty($template);
    }

    public function testConstructorWithDefaultFrom(): void
    {
        // GIVEN admin_email is configured (in setUp)
        // WHEN we construct a TwigEmail with no from address
        $email = new TwigEmail();

        // THEN the from address should fall back to admin_email
        $from = $email->getFrom();
        $this->assertNotEmpty($from);
    }

    public function testConstructorWithArrayAdminEmail(): void
    {
        // GIVEN admin_email is configured as an associative array [email => name]
        Config::modify()->set(TwigEmail::class, 'admin_email', ['admin@example.com' => 'Admin']);

        // WHEN we construct a TwigEmail with no from address
        $email = new TwigEmail();

        // THEN the from address should be set from the array config
        $from = $email->getFrom();
        $this->assertNotEmpty($from);
    }

    public function testMethodChaining(): void
    {
        // GIVEN a TwigEmail instance
        $email = new TwigEmail('sender@example.com');

        // WHEN we chain multiple setter methods together
        $result = $email
            ->setHTMLTemplate('test')
            ->addData('key', 'value')
            ->removeData('key')
            ->setPlainTemplate('plain');

        // THEN the result should still be a TwigEmail (all setters return static)
        $this->assertInstanceOf(TwigEmail::class, $result);
    }

    public function testSendCallsMailer(): void
    {
        // GIVEN a mock mailer registered in the Injector
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        // WHEN we construct an email with body and call send()
        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setBody('<p>Hello</p>');
        $email->send();

        // THEN the mailer's send() should have been called exactly once
    }

    public function testSendPlainCallsMailer(): void
    {
        // GIVEN a mock mailer registered in the Injector
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        // WHEN we construct an email with body and call sendPlain()
        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setBody('<p>Hello</p>');
        $email->sendPlain();

        // THEN the mailer's send() should have been called exactly once
    }

    public function testSendWithHtmlBodyButNoTemplate(): void
    {
        // GIVEN a mock mailer and an email with direct HTML body (no template)
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->html('<p>Direct HTML</p>');

        // WHEN we call send()
        $email->send();

        // THEN the direct HTML body should be preserved (not overwritten by template rendering)
        $this->assertSame('<p>Direct HTML</p>', $email->getHtmlBody());
    }

    public function testSendWithDataRendersTemplate(): void
    {
        // GIVEN a mock mailer, an email with a Twig template and data variables
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setHTMLTemplate('email_test');
        $email->addData('Subject', 'Hello');
        $email->addData('Body', 'World');

        // WHEN we call send()
        $email->send();

        // THEN the HTML body should contain rendered template output with the data variables
        $htmlBody = $email->getHtmlBody();
        $this->assertStringContainsString('Hello', $htmlBody);
        $this->assertStringContainsString('World', $htmlBody);
    }

    public function testSendPlainGeneratesPlainTextFromHtml(): void
    {
        // GIVEN a mock mailer and an email with a Twig HTML template
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setHTMLTemplate('email_test');
        $email->addData('Subject', 'Test Subject');
        $email->addData('Body', 'Test Body');

        // WHEN we call sendPlain() (which strips HTML to generate plain text)
        $email->sendPlain();

        // THEN the text body should be populated with a plain-text version
        $textBody = $email->getTextBody();
        $this->assertNotEmpty($textBody);
    }

    public function testSendWithNoContentNoTemplateDoesNotThrow(): void
    {
        // GIVEN a mock mailer and an email with no body and no template
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');

        // WHEN we call send() with nothing to render
        $email->send();

        // THEN it should send without throwing an error (graceful empty email)
    }

    public function testSendWithExplicitBodyAndNoDataUsesBody(): void
    {
        // GIVEN a mock mailer, an email with both an explicit HTML body and a template set,
        // but no data added (dataHasBeenSet remains false)
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->html('<p>Explicit HTML</p>');
        $email->setHTMLTemplate('email_test');

        // WHEN we call send() without calling addData/setData
        $email->send();

        // THEN the explicit HTML body should be used (template rendering skipped)
        $this->assertSame('<p>Explicit HTML</p>', $email->getHtmlBody());
    }

    public function testSendPlainWithExplicitPlainBody(): void
    {
        // GIVEN a mock mailer, an email with an explicit plain text body and a template,
        // but no data added (dataHasBeenSet remains false)
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->text('Plain text content');
        $email->setHTMLTemplate('email_test');

        // WHEN we call sendPlain() without calling addData/setData
        $email->sendPlain();

        // THEN the explicit plain text should be used (template rendering skipped)
        $this->assertSame('Plain text content', $email->getTextBody());
    }
}
