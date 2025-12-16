<?php

class TemplateController
{
    private $templateModel;

    public function __construct()
    {
        $this->templateModel = new Template();
    }

    public function index()
    {
        try {
            $templates = $this->templateModel->getAll();
            Response::json(['success' => true, 'data' => $templates], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch templates: ' . $e->getMessage(), 500);
        }
    }

    public function active()
    {
        try {
            $templates = $this->templateModel->getActive();
            Response::json(['success' => true, 'data' => $templates], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch active templates: ' . $e->getMessage(), 500);
        }
    }

    public function store()
    {
        try {
            $data = Request::all();
            Validator::make($data, [
                'template_name' => 'required|string',
                'template_content' => 'required|string',
                'status' => 'integer'
            ])->validate();

            $templateId = $this->templateModel->create($data);
            Response::json(['success' => true, 'message' => 'Template created', 'data' => ['id' => $templateId]], 201);
        } catch (Exception $e) {
            Response::error('Failed to create template: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $template = $this->templateModel->findById($id);
            if (!$template) Response::notFound('Template not found');
            Response::json(['success' => true, 'data' => $template], 200);
        } catch (Exception $e) {
            Response::error('Failed to fetch template: ' . $e->getMessage(), 500);
        }
    }

    public function update($id)
    {
        try {
            $template = $this->templateModel->findById($id);
            if (!$template) Response::notFound('Template not found');
            
            $data = Request::all();
            $this->templateModel->update($id, $data);
            Response::json(['success' => true, 'message' => 'Template updated'], 200);
        } catch (Exception $e) {
            Response::error('Failed to update template: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $template = $this->templateModel->findById($id);
            if (!$template) Response::notFound('Template not found');
            
            $this->templateModel->delete($id);
            Response::json(['success' => true, 'message' => 'Template deleted'], 200);
        } catch (Exception $e) {
            Response::error('Failed to delete template: ' . $e->getMessage(), 500);
        }
    }

    public function render($id)
    {
        try {
            $template = $this->templateModel->findById($id);
            if (!$template) Response::notFound('Template not found');
            
            $data = Request::all();
            $rendered = $this->templateModel->render($template['template_content'], $data);
            
            Response::json(['success' => true, 'data' => ['rendered_content' => $rendered]], 200);
        } catch (Exception $e) {
            Response::error('Failed to render template: ' . $e->getMessage(), 500);
        }
    }
}

