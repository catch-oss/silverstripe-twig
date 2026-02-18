<?php

namespace Azt3k\SS\Twig;

use SilverStripe\View\Requirements;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Twig\TemplateWrapper;

trait TwigRenderer {

    /**
     * @var bool
     */
    protected $includeRequirements = true;

    public function __get(string $name): mixed {

        if ($name == 'dic') {
            return $this->dic = new TwigContainer;
        } else {
            return parent::__get($name);
        }
    }

    public function __isset(string $name): bool {

        return $this->hasMethod($name) ? false : true;
    }

    /**
     * Overrides the renderWith method for DOs
     */
    public function renderWith($template, ModelData|array $customFields = []): DBHTMLText {

        $data = ($this->customisedObject) ? $this->customisedObject : $this;

        if (is_array($customFields) || $customFields instanceof ModelData) {
            $data = $data->customise($customFields);
        }

        $templates = is_array($template) ? $template : [$template];

        try {
            $html = $this->renderTwig($templates, $data);
            $field = DBHTMLText::create();
            $field->setValue($html);
            return $field;
        } catch (\InvalidArgumentException $e) {
            return parent::renderWith($template, $customFields);
        }

    }

    public function render(mixed $params = null): string {

        $obj = ($this->customisedObj) ? $this->customisedObj : $this;
        if ($params) {
            $obj = $this->customise($params);
        }

        $action = method_exists($this, 'getAction')
            ? $this->getAction()
            : null;

        return $this->renderTwig(
            $this->getTemplateList($action),
            $obj
        );
    }

    protected function renderTwig(array $templates, mixed $context): string {
        $render = $this->getTwigTemplate($templates)->render([
            $this->dic['twig.controller_variable_name'] => $context
        ]);

        // inject any 'required' assets in the output, e.g. userforms JS
        if ($this->includeRequirements)
            $render = Requirements::includeInHTML($render);

        return $render;
    }

    public function customise(mixed $params): static {

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    protected function getTwigTemplate(array $templates): TemplateWrapper {

        $templates = $this->applyExtensionResult('ModifyTwigTemplates', $templates) ?? $templates;

        if (!$templates) {
            throw new \InvalidArgumentException("No templates available, perhaps the extension is borked");
        }

        $loader = $this->dic['twig.loader'];
        $extensions = (array) $this->dic['twig.extensions'];

        foreach ($templates as $value) {
            $value = $this->resolveTemplateName($value);
            $loaded = $this->findLoadableTemplate($loader, $extensions, $value);
            if ($loaded !== null) {
                return $loaded;
            }
        }

        throw new \InvalidArgumentException("No templates for " . print_r($templates, 1) . " exist");
    }

    /**
     * Extracts the first valid string result from an extension hook call
     */
    private function applyExtensionResult(string $hook, mixed ...$args): mixed
    {
        $ret = $this->extend($hook, ...$args);
        return (is_array($ret) && count($ret) > 0) ? $ret[0] : null;
    }

    /**
     * Normalises a template entry — handles both string names and SS-style arrays
     * like ['type' => 'Includes', 0 => 'SilverStripe\Security\Security_login']
     */
    private function resolveTemplateName(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value[0];
        }

        $override = $this->applyExtensionResult('ModifyTwigTemplate', $value);
        if (is_string($override)) {
            $value = $override;
        }

        return $value;
    }

    /**
     * Tries each registered file extension to find a loadable template
     */
    private function findLoadableTemplate($loader, array $extensions, string $name): ?TemplateWrapper
    {
        foreach ($extensions as $extension) {
            if ($loader->exists($name . $extension)) {
                return $this->dic['twig']->load($name . $extension);
            }
        }
        return null;
    }

    /**
     * Build template list from class hierarchy
     */
    public function buildTemplatesFromClassName(string $className, ?string $action = null): array {

        // init templates
        $templates = [];

        // Add action-specific templates for inheritance chain
        if ($action && $action != 'index') {
            $parentClass = $className;
            while (
                $parentClass &&
                $parentClass != 'SilverStripe\Control\Controller' &&
                $parentClass != 'SilverStripe\ORM\DataObject'
            ) {
                $classPath = str_replace('\\', '/', $parentClass);
                $templates[] = $classPath . '_' . $action;
                $parentClass = get_parent_class($parentClass);
            }
        }

        // Add controller templates for inheritance chain
        $parentClass = $className;
        while (
            $parentClass &&
            $parentClass != 'SilverStripe\Control\Controller' &&
            $parentClass != 'SilverStripe\ORM\DataObject'
        ) {
            $classPath = str_replace('\\', '/', $parentClass);
            $templates[] = $classPath;
            $parentClass = get_parent_class($parentClass);
        }

        return $templates;
    }

    protected function getTemplateList(?string $action = null): array {

        // Hard-coded templates
        if (!empty($this->templates[$action])) {
            $templates = $this->templates[$action];
        } elseif (!empty($this->templates['index'])) {
            $templates = $this->templates['index'];
        } elseif (!empty($this->template)) {
            $templates = is_array($this->template) ? $this->template : [$this->template];
        } else {
            // build template list
            // get_class and $this->className return different things sometimes
            $templates = $this->buildTemplatesFromClassName(get_class($this), $action);
            if (!empty($this->ClassName) && $this->ClassName !== get_class($this)) {
                $templates = array_unique(array_merge(
                    $templates,
                    $this->buildTemplatesFromClassName($this->ClassName, $action)
                ));
            }
        }

        // if the current class has a getHTMLTemplate method try it
        if (method_exists($this, 'getHTMLTemplate')) {
            $templates = array_unique(array_merge(
                [$this->getHTMLTemplate()],
                $templates
            ));
        }

        return $templates;
    }

}
