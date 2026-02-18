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
        $email = new TwigEmail('sender@example.com');
        $from = $email->getFrom();
        $this->assertNotEmpty($from);
    }

    public function testConstructorSetsTo(): void
    {
        $email = new TwigEmail('', 'recipient@example.com');
        $to = $email->getTo();
        $this->assertNotEmpty($to);
    }

    public function testConstructorSetsSubject(): void
    {
        $email = new TwigEmail('', '', 'Test Subject');
        $this->assertSame('Test Subject', $email->getSubject());
    }

    public function testConstructorSetsCC(): void
    {
        $email = new TwigEmail('sender@example.com', '', '', '', 'cc@example.com');
        $cc = $email->getCc();
        $this->assertNotEmpty($cc);
    }

    public function testConstructorSetsBCC(): void
    {
        $email = new TwigEmail('sender@example.com', '', '', '', '', 'bcc@example.com');
        $bcc = $email->getBcc();
        $this->assertNotEmpty($bcc);
    }

    public function testConstructorSetsReturnPath(): void
    {
        $email = new TwigEmail('sender@example.com', '', '', '', '', '', 'return@example.com');
        $returnPath = $email->getReturnPath();
        $this->assertSame('return@example.com', $returnPath->getAddress());
    }

    public function testSetBodyWithStringCallsHtml(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->setBody('<p>Test body</p>');
        $this->assertSame('<p>Test body</p>', $email->getHtmlBody());
    }

    public function testSetBodyWithNullDoesNotThrow(): void
    {
        $email = new TwigEmail('sender@example.com');
        $result = $email->setBody(null);
        $this->assertInstanceOf(TwigEmail::class, $result);
    }

    public function testSetBodyWithAbstractPart(): void
    {
        $email = new TwigEmail('sender@example.com');
        $part = new TextPart('Hello plain');
        $result = $email->setBody($part);
        $this->assertInstanceOf(TwigEmail::class, $result);
    }

    public function testGetDataReturnsModelData(): void
    {
        $email = new TwigEmail('sender@example.com');
        $data = $email->getData();
        $this->assertInstanceOf(ModelData::class, $data);
    }

    public function testGetDataIncludesIsEmail(): void
    {
        $email = new TwigEmail('sender@example.com');
        $data = $email->getData();
        $this->assertTrue($data->IsEmail);
    }

    public function testGetDataIncludesBaseURL(): void
    {
        $email = new TwigEmail('sender@example.com');
        $data = $email->getData();
        $this->assertNotEmpty($data->BaseURL);
    }

    public function testGetDataDoesNotOverrideExistingIsEmail(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->addData('IsEmail', false);
        $data = $email->getData();
        $this->assertFalse($data->IsEmail);
    }

    public function testSetDataWithArray(): void
    {
        $email = new TwigEmail('sender@example.com');
        $result = $email->setData(['Foo' => 'bar']);
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertSame('bar', $data->Foo);
    }

    public function testSetDataWithModelData(): void
    {
        $email = new TwigEmail('sender@example.com');
        $modelData = ArrayData::create(['Key' => 'value']);
        $result = $email->setData($modelData);
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertSame('value', $data->Key);
    }

    public function testAddDataWithKeyValue(): void
    {
        $email = new TwigEmail('sender@example.com');
        $result = $email->addData('TestKey', 'TestValue');
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertSame('TestValue', $data->TestKey);
    }

    public function testAddDataWithArray(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->addData(['A' => 1, 'B' => 2]);
        $data = $email->getData();
        $this->assertSame(1, $data->A);
        $this->assertSame(2, $data->B);
    }

    public function testRemoveData(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->addData('RemoveMe', 'value');
        $result = $email->removeData('RemoveMe');
        $this->assertInstanceOf(TwigEmail::class, $result);
        $data = $email->getData();
        $this->assertNull($data->RemoveMe);
    }

    public function testSetHTMLTemplate(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->setHTMLTemplate('MyTemplate');
        $this->assertSame('MyTemplate', $email->getHTMLTemplate());
    }

    public function testSetHTMLTemplateStripsSSExtension(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->setHTMLTemplate('MyTemplate.ss');
        $this->assertSame('MyTemplate', $email->getHTMLTemplate());
    }

    public function testSetPlainTemplate(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->setPlainTemplate('PlainTemplate');
        $this->assertSame('PlainTemplate', $email->getPlainTemplate());
    }

    public function testSetPlainTemplateStripsSSExtension(): void
    {
        $email = new TwigEmail('sender@example.com');
        $email->setPlainTemplate('PlainTemplate.ss');
        $this->assertSame('PlainTemplate', $email->getPlainTemplate());
    }

    public function testGetPlainTemplateDefaultsToEmpty(): void
    {
        $email = new TwigEmail('sender@example.com');
        $this->assertSame('', $email->getPlainTemplate());
    }

    public function testGetHTMLTemplateDefaultUsesSSViewer(): void
    {
        $email = new TwigEmail('sender@example.com');
        // Without setting a template, getHTMLTemplate uses SSViewer::get_templates_by_class()
        $template = $email->getHTMLTemplate();
        // Returns array of template candidates from the class hierarchy
        $this->assertIsArray($template);
        $this->assertNotEmpty($template);
    }

    public function testConstructorWithDefaultFrom(): void
    {
        $email = new TwigEmail();
        $from = $email->getFrom();
        $this->assertNotEmpty($from);
    }

    public function testConstructorWithArrayAdminEmail(): void
    {
        Config::modify()->set(TwigEmail::class, 'admin_email', ['admin@example.com' => 'Admin']);
        $email = new TwigEmail();
        $from = $email->getFrom();
        $this->assertNotEmpty($from);
    }

    public function testMethodChaining(): void
    {
        $email = new TwigEmail('sender@example.com');
        $result = $email
            ->setHTMLTemplate('test')
            ->addData('key', 'value')
            ->removeData('key')
            ->setPlainTemplate('plain');
        $this->assertInstanceOf(TwigEmail::class, $result);
    }

    public function testSendCallsMailer(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setBody('<p>Hello</p>');
        $email->send();
    }

    public function testSendPlainCallsMailer(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setBody('<p>Hello</p>');
        $email->sendPlain();
    }

    public function testSendWithHtmlBodyButNoTemplate(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->html('<p>Direct HTML</p>');
        $email->send();

        // HTML body should be preserved
        $this->assertSame('<p>Direct HTML</p>', $email->getHtmlBody());
    }

    public function testSendWithDataRendersTemplate(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setHTMLTemplate('email_test');
        $email->addData('Subject', 'Hello');
        $email->addData('Body', 'World');
        $email->send();

        // After send, HTML body should have rendered content
        $htmlBody = $email->getHtmlBody();
        $this->assertStringContainsString('Hello', $htmlBody);
        $this->assertStringContainsString('World', $htmlBody);
    }

    public function testSendPlainGeneratesPlainTextFromHtml(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->setHTMLTemplate('email_test');
        $email->addData('Subject', 'Test Subject');
        $email->addData('Body', 'Test Body');
        $email->sendPlain();

        // After sendPlain, text body should exist
        $textBody = $email->getTextBody();
        $this->assertNotEmpty($textBody);
    }

    public function testSendWithNoContentNoTemplateDoesNotThrow(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        // No body, no template — should just send without error
        $email->send();
    }

    public function testSendWithExplicitBodyAndNoDataUsesBody(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->html('<p>Explicit HTML</p>');
        $email->setHTMLTemplate('email_test');
        // Don't call addData/setData so dataHasBeenSet remains false
        $email->send();

        // With htmlBody set and dataHasBeenSet=false, it should use the explicit body
        $this->assertSame('<p>Explicit HTML</p>', $email->getHtmlBody());
    }

    public function testSendPlainWithExplicitPlainBody(): void
    {
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->expects($this->once())->method('send');
        Injector::inst()->registerService($mockMailer, MailerInterface::class);

        $email = new TwigEmail('sender@example.com', 'to@example.com', 'Test');
        $email->text('Plain text content');
        $email->setHTMLTemplate('email_test');
        // Don't call addData/setData so dataHasBeenSet remains false
        $email->sendPlain();

        $this->assertSame('Plain text content', $email->getTextBody());
    }
}
