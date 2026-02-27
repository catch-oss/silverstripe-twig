<?php

namespace Azt3k\SS\Twig;

trait TwigController {

    use TwigRenderer;

    public function __get(string $name): mixed
    {
        if ($name == 'dic') {
            return $this->dic = new TwigContainer;
        } else {
            return parent::__get($name);
        }
    }

    public function __isset(string $name): bool
    {
        return $this->hasMethod($name) ? false : true;
    }

    protected function handleAction($request, $action)
    {
        // urlParams, requestParams, and action are set for backward compatability
        foreach ($request->latestParams() as $k => $v) {
            if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
        }

        $this->action = str_replace("-","_",$action);
        $this->requestParams = $request->requestVars();
        if(!$this->action) $this->action = 'index';

        if (!$this->hasAction($this->action)) {
            $this->httpError(404, "The action '$this->action' does not exist in class " . get_class($this));
        }

        // run & init are manually disabled, because they create infinite loops and other dodgy situations
        if (!$this->checkAccessAction($this->action) || in_array(strtolower($this->action), array('run', 'init'))) {
            return $this->httpError(403, "Action '$this->action' isn't allowed on class " . get_class($this));
        }

        // If no explicit action method exists, render the template directly
        if (!$this->hasMethod($this->action)) {
            return $this->renderTwig($this->getTemplateList($this->action), $this);
        }

        $result = $this->{$this->action}($request);

        // If the action returns an array, customise with it before rendering the template;
        // otherwise return the action result as-is (e.g. HTTPResponse, string)
        return is_array($result)
            ? $this->renderTwig($this->getTemplateList($this->action), $this->customise($result))
            : $result;
    }
}
