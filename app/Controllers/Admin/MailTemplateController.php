<?php

namespace App\Controllers\Admin;

use Core\Controller;
use Core\Session;
use App\Models\MailTemplate;

class MailTemplateController extends Controller
{
    private MailTemplate $templateModel;

    public function __construct()
    {
        parent::__construct();
        $this->templateModel = new MailTemplate();
    }

    public function index(): void
    {
        $this->render('admin/mail-templates/index.twig', [
            'templates' => $this->templateModel->findAll('name', 'ASC'),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/mail-templates/create.twig');
    }

    public function store(): void
    {
        $name = trim($this->input('name', ''));
        $slug = trim($this->input('slug', ''));

        if (empty($name) || empty($slug)) {
            Session::flash('error', 'Naam en slug zijn verplicht.');
            $this->redirect('/admin/mail-templates/create');
            return;
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'subject_nl' => $this->input('subject_nl', ''),
            'subject_fr' => $this->input('subject_fr', ''),
            'body_nl' => $this->input('body_nl', ''),
            'body_fr' => $this->input('body_fr', ''),
            'available_variables' => $this->input('available_variables', ''),
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];

        $this->templateModel->create($data);
        Session::flash('success', 'Mail template aangemaakt.');
        $this->redirect('/admin/mail-templates');
    }

    public function edit(int $id): void
    {
        $template = $this->templateModel->findById($id);
        if (!$template) {
            Session::flash('error', 'Template niet gevonden.');
            $this->redirect('/admin/mail-templates');
            return;
        }

        $this->render('admin/mail-templates/edit.twig', [
            'template' => $template,
        ]);
    }

    public function update(int $id): void
    {
        $name = trim($this->input('name', ''));
        if (empty($name)) {
            Session::flash('error', 'Naam is verplicht.');
            $this->redirect("/admin/mail-templates/{$id}/edit");
            return;
        }

        $data = [
            'name' => $name,
            'slug' => trim($this->input('slug', '')),
            'subject_nl' => $this->input('subject_nl', ''),
            'subject_fr' => $this->input('subject_fr', ''),
            'body_nl' => $this->input('body_nl', ''),
            'body_fr' => $this->input('body_fr', ''),
            'sms_nl' => $this->input('sms_nl', ''),
            'sms_fr' => $this->input('sms_fr', ''),
            'available_variables' => $this->input('available_variables', ''),
            'is_active' => $this->input('is_active') ? 1 : 0,
        ];

        $this->templateModel->update($id, $data);
        Session::flash('success', 'Mail template bijgewerkt.');
        $this->redirect("/admin/mail-templates/{$id}/edit");
    }

    public function destroy(int $id): void
    {
        $this->templateModel->delete($id);
        Session::flash('success', 'Mail template verwijderd.');
        $this->redirect('/admin/mail-templates');
    }
}
