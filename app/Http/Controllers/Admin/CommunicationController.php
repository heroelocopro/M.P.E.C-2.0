<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/communication', [
            'recipient_count' => User::query()->whereNotNull('email')->count(),
        ]);
    }

    public function sendAll(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
        ]);
        $recipients = User::query()->whereNotNull('email')->pluck('email')->all();

        abort_if($recipients === [], 422, 'No hay destinatarios con correo registrado.');
        Mail::raw($data['message'], function ($mail) use ($recipients, $data): void {
            $mail->bcc($recipients)->subject($data['subject']);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Correo enviado a toda la comunidad registrada.']);

        return back();
    }
}
