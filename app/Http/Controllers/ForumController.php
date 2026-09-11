<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreForumReplyRequest;
use App\Http\Requests\StoreForumTopicRequest;
use App\Models\Classroom;
use App\Models\ForumTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [UserRole::Student, UserRole::Teacher], true), 403);
        $classrooms = Classroom::query()
            ->when($user->role === UserRole::Teacher,
                fn ($query) => $query->whereHas('teacherAssignments', fn ($assignment) => $assignment->where('teacher_id', $user->id)),
                fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('status', 'activa')->whereHas('student', fn ($student) => $student->where('user_id', $user->id))))
            ->with(['grade', 'campus'])->get();
        $topics = ForumTopic::query()->whereIn('classroom_id', $classrooms->modelKeys())
            ->with('user:id,name')->withCount('replies')->latest()->paginate(15)->withQueryString();

        return Inertia::render('forum', [
            'classrooms' => $classrooms->map(fn ($classroom) => ['id' => $classroom->id, 'label' => "{$classroom->grade->name} {$classroom->name} · {$classroom->campus->name}"]),
            'topics' => $topics,
        ]);
    }

    public function show(Request $request, ForumTopic $forumTopic): Response
    {
        abort_unless(Classroom::query()->findOrFail($forumTopic->classroom_id)->accessibleTo($request->user()), 403);

        return Inertia::render('forum-topic', [
            'topic' => $forumTopic->load('user:id,name'),
            'replies' => $forumTopic->replies()->with('user:id,name')->oldest()->paginate(30)->withQueryString(),
        ]);
    }

    public function store(StoreForumTopicRequest $request): RedirectResponse
    {
        $topic = ForumTopic::query()->create([...$request->validated(), 'user_id' => $request->user()->id]);

        return to_route('forum.show', $topic);
    }

    public function reply(StoreForumReplyRequest $request, ForumTopic $forumTopic): RedirectResponse
    {
        $forumTopic->replies()->create([...$request->validated(), 'user_id' => $request->user()->id]);

        return back();
    }
}
