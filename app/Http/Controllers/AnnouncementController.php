<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements.
     */
    public function index()
    {
        $announcements = Announcement::where('active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Announcements', [
            'announcements' => $announcements,
        ]);
    }
}
