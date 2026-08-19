<?php

namespace App\Http\Controllers;

use App\Events\PrivateMessageSent;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PrivateMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $messages = PrivateMessage::with('user')
            ->where('user_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->oldest()
            ->get();
        $receiver_id = User::where('id', '!=', Auth::id())->get();
        $authUser = Auth::user();

        return Inertia::render('private/Index', [
            // Mengambil pesan yang sudah ada di database
            'messages' => $messages,
            'authUser' => $authUser,
            'receiver_id' => $receiver_id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Step 1: Mencapai Controller');
        // 1. Ambil data receiver_id dari request (sekarang berupa array, misal: [2, 3])
        $receiverIds = $request->receiver_id;
        $messageText = $request->message; // pastikan nama fieldnya sesuai, sesuaikan jika 'message' atau 'messages'
        $userId = Auth::id(); // atau $userId yang sudah Anda definisikan sebelumnya

        $message = PrivateMessage::create([
            'user_id'     => $userId,
            'receiver_id' => $receiverIds,
            'message'     => $messageText,
        ]);
        $message->load('user'); // Memuat relasi user agar bisa diakses saat broadcast
        Log::info('Step 2: Message berhasil dibuat dengan ID: ' . $message->id);
        // $message->load('user');
        broadcast(new PrivateMessageSent($message));
        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $messages = PrivateMessage::with('user')
            ->where(function ($query) use ($id) {
                // Kondisi 1: Anda pengirimnya, dan dia penerimanya
                $query->where('user_id', Auth::id())
                    ->where('receiver_id', $id);
            })
            ->orWhere(function ($query) use ($id) {
                // Kondisi 2: Dia pengirimnya, dan Anda penerimanya
                $query->where('user_id', $id)
                    ->where('receiver_id', Auth::id());
            })
            ->oldest() // Mengurutkan dari pesan terlama ke terbaru (cocok untuk room chat)
            ->get();
        $receiver = User::find($id);
        $authUser = Auth::user();

        return Inertia::render('private/Index', [
            // Mengambil pesan yang sudah ada di database
            'messages' => $messages,
            'authUser' => $authUser,
            'receiver_id' => $receiver,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
