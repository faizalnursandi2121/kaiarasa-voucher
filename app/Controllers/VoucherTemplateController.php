<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\FlashHelper;
use App\Helpers\TemplateHelper;
use App\Models\Logo;
use App\Models\Setting;
use App\Models\Config;
use App\Models\VoucherTemplateModel;

class VoucherTemplateController extends Controller
{
    public function __construct()
    {
        Middleware::auth();
    }

    /**
     * Resolve session slug ke row router (null = konteks global).
     * Redirect ke /settings bila session tidak ditemukan.
     */
    private function resolveSession(?string $session): ?array
    {
        if ($session === null || $session === '') {
            return null;
        }

        $router = (new Config)->getSession($session);
        if (! $router) {
            FlashHelper::set('error', 'toasts.router_not_found', '', [], true);
            header('Location: /settings');
            exit;
        }

        return $router;
    }

    /** Kembali ke index sesuai konteks (per-session atau global). */
    private function indexRedirect(?string $session): void
    {
        header('Location: '.($session ? '/'.rawurlencode($session).'/voucher-templates' : '/settings/voucher-templates'));
        exit;
    }

    public function index(?string $session = null)
    {
        $templateModel = new VoucherTemplateModel;

        $router = $this->resolveSession($session);
        $routerId = $router !== null ? (int) $router['id'] : null;

        $templates = $templateModel->getAll($routerId);

        // Fetch current default template
        $settingModel = new Setting;
        $defaultTemplate = $settingModel->get('default_voucher_template', 'default');

        $data = [
            'templates' => $templates,
            'defaultTemplate' => $defaultTemplate,
            'sessionName' => $session,
            'session' => $session,
        ];

        return $this->view('settings/voucher_templates/index', $data);
    }

    public function preview($id)
    {
        $content = '';
        if ($id === 'default') {
            $content = TemplateHelper::getDefaultTemplate();
        } else {
            $templateModel = new VoucherTemplateModel;
            $tpl = $templateModel->getById($id);
            if ($tpl) {
                $content = $tpl['content'];
            }
        }

        echo TemplateHelper::getPreviewPage($content);
    }

    public function add(?string $session = null)
    {
        $logoModel = new Logo;
        $logos = $logoModel->getAll();
        $logoMap = [];
        foreach ($logos as $l) {
            $logoMap[$l['id']] = $l['path'];
        }

        $data = [
            'logoMap' => $logoMap,
            'sessionName' => $session,
            'session' => $session,
        ];

        return $this->view('settings/voucher_templates/add', $data);
    }

    public function store(?string $session = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $name = $_POST['name'] ?? 'Untitled';
        $content = $_POST['content'] ?? '';

        // Konteks per-session: template terikat ke router tsb.
        // Tanpa konteks (global) -> session_id NULL + label 'global'.
        $router = $this->resolveSession($session);

        $data = [
            'router_id' => $router !== null ? (int) $router['id'] : 0,
            'session_name' => $router !== null ? $router['session_name'] : 'global',
            'name' => $name,
            'content' => $content,
            'session_id' => $router !== null ? (int) $router['id'] : null,
        ];

        $templateModel = new VoucherTemplateModel;
        $templateModel->add($data);

        FlashHelper::set('success', 'toasts.template_created', 'toasts.template_created_desc', ['name' => $name], true);
        $this->indexRedirect($session);
    }

    public function edit($id, ?string $session = null)
    {
        $templateModel = new VoucherTemplateModel;
        $template = $templateModel->getById($id);

        if (! $template) {
            $this->indexRedirect($session);
        }

        $logoModel = new Logo;
        $logos = $logoModel->getAll();
        $logoMap = [];
        foreach ($logos as $l) {
            $logoMap[$l['id']] = $l['path'];
        }

        $data = [
            'template' => $template,
            'logoMap' => $logoMap,
            'sessionName' => $session,
            'session' => $session,
        ];

        return $this->view('settings/voucher_templates/edit', $data);
    }

    public function update(?string $session = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $content = $_POST['content'] ?? '';

        $data = [
            'name' => $name,
            'content' => $content,
        ];

        $templateModel = new VoucherTemplateModel;
        $templateModel->update($id, $data);

        FlashHelper::set('success', 'toasts.template_updated', 'toasts.template_updated_desc', ['name' => $name], true);
        $this->indexRedirect($session);
    }

    public function delete(?string $session = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $id = $_POST['id'] ?? '';

        $templateModel = new VoucherTemplateModel;
        $templateModel->delete($id);

        FlashHelper::set('success', 'toasts.template_deleted', 'toasts.template_deleted_desc', [], true);
        $this->indexRedirect($session);
    }

    public function setDefault(?string $session = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $id = $_POST['id'] ?? 'default';

        $settingModel = new Setting;
        $settingModel->set('default_voucher_template', $id);

        FlashHelper::set('success', 'toasts.default_template_set', 'toasts.default_template_set_desc', [], true);
        $this->indexRedirect($session);
    }
}
