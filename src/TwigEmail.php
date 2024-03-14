<?php

namespace Azt3k\SS\Twig;

use Azt3k\SS\Twig\TwigViewableData;
use RuntimeException;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\ViewableData;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\AbstractPart;
/* 
injected class which is mostly copy/paste of Email unfortunately.
*/

class TwigEmail extends Email
{

    /**
     * The name of the HTML template to render the email with (without *.ss extension)
     */
    private string $HTMLTemplate = '';

    /**
     * The name of the plain text template to render the plain part of the email with
     */
    private string $plainTemplate = '';

    /**
     * Additional data available in a template.
     * Used in the same way than {@link ViewableData->customize()}.
     */
    private TwigViewableData $data;

    private bool $dataHasBeenSet = false;


    public function __construct(
        string|array $from = '',
        string|array $to = '',
        string $subject = '',
        string $body = '',
        string|array $cc = '',
        string|array $bcc = '',
        string $returnPath = ''
    ) {
        parent::__construct();
        if ($from) {
            $this->setFrom($from);
        } else {
            $this->setFrom($this->getDefaultFrom());
        }
        if ($to) {
            $this->setTo($to);
        }
        if ($subject) {
            $this->setSubject($subject);
        }
        if ($body) {
            $this->setBody($body);
        }
        if ($cc) {
            $this->setCC($cc);
        }
        if ($bcc) {
            $this->setBCC($bcc);
        }
        if ($returnPath) {
            $this->setReturnPath($returnPath);
        }
        $this->data = TwigViewableData::create();
    }

    private function getDefaultFrom(): string|array
    {
        // admin_email can have a string or an array config
        // https://docs.silverstripe.org/en/4/developer_guides/email/#administrator-emails
        $adminEmail = $this->config()->get('admin_email');
        if (is_array($adminEmail) && count($adminEmail ?? []) > 0) {
            $email = array_keys($adminEmail)[0];
            $defaultFrom = [$email => $adminEmail[$email]];
        } else {
            if (is_string($adminEmail)) {
                $defaultFrom = $adminEmail;
            } else {
                $defaultFrom = '';
            }
        }
        if (empty($defaultFrom)) {
            $host = Director::host();
            if (empty($host)) {
                throw new RuntimeException('Host not defined');
            }
            $defaultFrom = sprintf('no-reply@%s', $host);
        }
        $this->extend('updateDefaultFrom', $defaultFrom);
        return $defaultFrom;
    }

    /**
     * Passing a string of HTML for $body will have no affect if you also call either setData() or addData()
     */
    public function setBody(AbstractPart|string $body = null): static
    {
        if ($body instanceof AbstractPart) {
            // pass to Symfony\Component\Mime\Message::setBody()
            return parent::setBody($body);
        }
        // Set HTML content directly.
        return $this->html($body);
    }


    /**
     * Get data which is exposed to the template
     *
     * The following data is exposed via this method by default:
     * IsEmail: used to detect if rendering an email template rather than a page template
     * BaseUrl: used to get the base URL for the email
     */
    public function getData(): ViewableData
    {
        $extraData = [
            'IsEmail' => true,
            'BaseURL' => Director::absoluteBaseURL(),
        ];
        $data = clone $this->data;
        foreach ($extraData as $key => $value) {
            if (is_null($data->{$key})) {
                $data->{$key} = $value;
            }
        }
        $this->extend('updateGetData', $data);
        return $data;
    }

    /**
     * Set template data
     *
     * Calling setData() once means that any content set via text()/html()/setBody() will have no effect
     */
    public function setData(array|ViewableData $data)
    {
        if (is_array($data)) {
            $data = ArrayData::create($data);
        }
        $this->data->setFailover($data);
        $this->dataHasBeenSet = true;
        return $this;
    }

    /**
     * Add data to be used in the template
     *
     * Calling addData() once means that any content set via text()/html()/setBody() will have no effect
     *
     * @param string|array $nameOrData can be either the name to add, or an array of [name => value]
     */
    public function addData(string|array $nameOrData, mixed $value = null): static
    {
        if (is_array($nameOrData)) {
            foreach ($nameOrData as $key => $val) {
                $this->data->{$key} = $val;
            }
        } else {
            $this->data->{$nameOrData} = $value;
        }
        $this->dataHasBeenSet = true;
        return $this;
    }

    /**
     * Remove a single piece of template data
     */
    public function removeData(string $name)
    {
        $this->data->{$name} = null;
        return $this;
    }

    public function getHTMLTemplate(): string
    {
        if ($this->HTMLTemplate) {
            return $this->HTMLTemplate;
        }

        return ThemeResourceLoader::inst()->findTemplate(
            SSViewer::get_templates_by_class(static::class, '', self::class),
            SSViewer::get_themes()
        );
    }

    /**
     * Set the template to render the email with
     */
    public function setHTMLTemplate(string $template): static
    {
        if (substr($template ?? '', -3) == '.ss') {
            $template = substr($template ?? '', 0, -3);
        }
        $this->HTMLTemplate = $template;
        return $this;
    }

    /**
     * Get the template to render the plain part with
     */
    public function getPlainTemplate(): string
    {
        return $this->plainTemplate;
    }

    /**
     * Set the template to render the plain part with
     */
    public function setPlainTemplate(string $template): static
    {
        if (substr($template ?? '', -3) == '.ss') {
            $template = substr($template ?? '', 0, -3);
        }
        $this->plainTemplate = $template;
        return $this;
    }

    /**
     * Send the message to the recipients
     */
    public function send(): void
    {
        $this->updateHtmlAndTextWithRenderedTemplates();
        Injector::inst()->get(MailerInterface::class)->send($this);
    }

    /**
     * Send the message to the recipients as plain-only
     */
    public function sendPlain(): void
    {
        $html = $this->getHtmlBody();
        $this->updateHtmlAndTextWithRenderedTemplates(true);
        $this->html(null);
        Injector::inst()->get(MailerInterface::class)->send($this);
        $this->html($html);
    }

    /**
     * Call html() and/or text() after rendering email templates
     * If either body html or text were previously explicitly set, those values will not be overwritten
     *
     * @param bool $plainOnly - if true then do not call html()
     */
    private function updateHtmlAndTextWithRenderedTemplates(bool $plainOnly = false): void
    {
        $htmlBody = $this->getHtmlBody();
        $plainBody = $this->getTextBody();

        // Ensure we can at least render something
        $htmlTemplate = $this->getHTMLTemplate();
        $plainTemplate = $this->getPlainTemplate();
        if (!$htmlTemplate && !$plainTemplate && !$plainBody && !$htmlBody) {
            return;
        }

        $htmlRender = null;
        $plainRender = null;

        if ($htmlBody && !$this->dataHasBeenSet) {
            $htmlRender = $htmlBody;
        }

        if ($plainBody && !$this->dataHasBeenSet) {
            $plainRender = $plainBody;
        }

        // Do not interfere with emails styles

        Requirements::clear();

        // Render plain
        if (!$plainRender && $plainTemplate) {
            $plainRender = $this->getData()->renderWith($plainTemplate, $this->getData())->Plain();
        }

        // Render HTML
        if (!$htmlRender && $htmlTemplate) {
            $htmlRender = $this->getData()->renderWith($htmlTemplate, $this->getData());
        }

        // Rendering is finished
        Requirements::restore();

        // Plain render fallbacks to using the html render with html tags removed
        if (!$plainRender && $htmlRender) {
            // call html_entity_decode() to ensure any encoded HTML is also stripped inside ->Plain()
            $dbField = DBField::create_field('HTMLFragment', html_entity_decode($htmlRender));
            $plainRender = $dbField->Plain();
        }

        // Handle edge case where no template was found
        if (!$htmlRender && $htmlBody) {
            $htmlRender = $htmlBody;
        }

        if (!$plainRender && $plainBody) {
            $plainRender = $plainBody;
        }

        if ($plainRender) {
            $this->text($plainRender);
        }
        if ($htmlRender && !$plainOnly) {
            $this->html($htmlRender);
        }
    }
}
