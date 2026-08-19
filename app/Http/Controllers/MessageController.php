<?php

namespace App\Http\Controllers;

use App\Events\PrivateMessageSent;
use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $authId = auth()->id();

        // Ambil semua ID user yang pernah chat dengan user login (baik sebagai sender maupun receiver)
        $partnerIds = PrivateMessage::where('user_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->get()
            ->map(function ($msg) use ($authId) {
                return $msg->user_id === $authId
                    ? $msg->receiver_id
                    : $msg->user_id;
            })
            ->unique()
            ->values();

        // Ambil data user + pesan terakhir + jumlah unread untuk tiap partner
        $contacts = User::whereIn('id', $partnerIds)
            ->get()
            ->map(function ($user) use ($authId) {
                $lastMessage = PrivateMessage::where(function ($q) use ($authId, $user) {
                    $q->where('user_id', $authId)->where('receiver_id', $user->id);
                })
                    ->orWhere(function ($q) use ($authId, $user) {
                        $q->where('user_id', $user->id)->where('receiver_id', $authId);
                    })
                    ->latest('created_at')
                    ->first();

                // $unreadCount = PrivateMessage::where('user_id', $user->id)
                //     ->where('receiver_id', $authId)
                //     ->where('is_read', false)
                //     ->count();

                return [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'avatar'      => $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                    'lastMessage' => $lastMessage?->message ?? '',
                    'time'        => $lastMessage?->created_at?->diffForHumans() ?? '',
                    // 'unread'      => $unreadCount,
                    'online'      => $user->last_seen_at?->gt(now()->subMinutes(5)) ?? false,
                ];
            })
            ->sortByDesc(fn($c) => $c['time'])
            ->values();

        $allUsers = User::where('id', '!=', $authId)
            ->get()
            ->map(fn($user) => [
                'id'     => $user->id,
                'name'   => $user->name,
                'avatar' => $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
            ]);
        // dd($contacts);
        // dd($authId);
        // dd($allUsers);
        return Inertia::render('messages/Index', [
            'users' => $contacts,
            'authId' => $authId,
            'allUsers' => $allUsers,
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
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
