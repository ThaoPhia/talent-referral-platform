<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Job;

class WelcomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = $request->string('search')->trim()->value();
        // '!' as the escape char works the same on SQLite, Postgres, and MySQL.
        $searchTerm = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        $perPage = $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 30], true) ? $perPage : 10;

        return Inertia::render('Welcome', [
            'search' => $search,
            'jobs' => Job::query()
                ->where('status', 'active')
                ->where('post_on', '<=', now())
                ->when($search !== '', fn($query) => $query->where(fn($query) => $query
                    ->whereRaw("lower(title) like lower(?) escape '!'", [$searchTerm])
                    ->orWhereRaw("lower(description) like lower(?) escape '!'", [$searchTerm])
                    ->orWhereRaw("lower(location) like lower(?) escape '!'", [$searchTerm])))
                ->latest('post_on')
                ->paginate($perPage, ['id', 'title', 'description', 'location', 'post_on'])
                ->withQueryString(),
        ]);
    }
}
