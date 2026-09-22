<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use App\Models\JobListing;
use App\Notifications\SocialActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = JobListing::query()
            ->with('poster:id,name')
            ->where('status', 'open')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($jobQuery) use ($search) {
                    $jobQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->input('location'), fn ($query, $location) => $query->where('location', 'like', "%{$location}%"))
            ->when($request->input('employment_type'), fn ($query, $type) => $query->where('employment_type', $type))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 50));

        $jobs->getCollection()->transform(function (JobListing $job) use ($request) {
            $job->setAttribute(
                'is_saved',
                $request->user()?->savedJobs()->whereKey($job->id)->exists() ?? false
            );

            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => $jobs->items(),
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'last_page' => $jobs->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, JobListing $job): JsonResponse
    {
        $job->load('poster:id,name');
        $job->setAttribute('is_saved', $request->user()?->savedJobs()->whereKey($job->id)->exists() ?? false);
        $job->setAttribute('has_applied', $request->user()?->jobApplications()->where('job_listing_id', $job->id)->exists() ?? false);

        return response()->json(['success' => true, 'data' => ['job' => $job]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'company_name' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:180'],
            'workplace_type' => ['required', 'in:onsite,hybrid,remote'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,internship,freelance'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
            'currency' => ['nullable', 'string', 'size:3'],
            'application_url' => ['nullable', 'url', 'max:2048'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $job = JobListing::create([...$data, 'posted_by' => $request->user()->id]);

        return response()->json(['success' => true, 'data' => ['job' => $job->load('poster:id,name')]], 201);
    }

    public function apply(Request $request, JobListing $job): JsonResponse
    {
        if ($job->posted_by === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot apply to your own job listing.'], 403);
        }

        if ($job->status !== 'open' || ($job->expires_at && $job->expires_at->isBefore(today()))) {
            return response()->json(['success' => false, 'message' => 'This job is no longer accepting applications.'], 422);
        }

        $data = $request->validate([
            'cover_letter' => ['required', 'string', 'max:5000'],
            'resume_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if (JobApplication::where('job_listing_id', $job->id)->where('applicant_id', $request->user()->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already applied to this job.'], 409);
        }

        $application = $job->applications()->create([
            ...$data,
            'applicant_id' => $request->user()->id,
            'status' => 'submitted',
        ]);

        return response()->json(['success' => true, 'data' => ['application' => $application]], 201);
    }

    public function save(Request $request, JobListing $job): JsonResponse
    {
        $request->user()->savedJobs()->syncWithoutDetaching([$job->id]);
        return response()->json(['success' => true, 'message' => 'Job saved.']);
    }

    public function unsave(Request $request, JobListing $job): JsonResponse
    {
        $request->user()->savedJobs()->detach($job->id);
        return response()->json(['success' => true, 'message' => 'Job removed from saved jobs.']);
    }

    public function myApplications(Request $request): JsonResponse
    {
        $applications = $request->user()->jobApplications()->with('job:id,title,company_name')->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => ['applications' => $applications->items()]]);
    }

    public function applications(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeOwner($request, $job);

        $applications = $job->applications()
            ->with('applicant:id,name,email')
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'data' => [
                'applications' => $applications->items(),
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'last_page' => $applications->lastPage(),
                ],
            ],
        ]);
    }

    public function updateApplicationStatus(Request $request, JobListing $job, JobApplication $application): JsonResponse
    {
        $this->authorizeOwner($request, $job);
        abort_unless($application->job_listing_id === $job->id, 404);

        $data = $request->validate([
            'status' => ['required', 'in:submitted,reviewing,interview,accepted,rejected'],
        ]);

        $application->update(['status' => $data['status']]);
        $application->applicant->notify(new SocialActivityNotification(
            'job_application_status_changed',
            "Your application for {$job->title} is now {$data['status']}.",
            ['job_id' => $job->id, 'application_id' => $application->id, 'status' => $data['status']]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Application status updated.',
            'data' => ['application' => $application->fresh('applicant:id,name,email')],
        ]);
    }

    public function close(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeOwner($request, $job);
        $job->update(['status' => 'closed']);

        return response()->json(['success' => true, 'message' => 'Job listing closed.']);
    }

    public function withdraw(Request $request, JobApplication $application): JsonResponse
    {
        abort_unless($application->applicant_id === $request->user()->id, 403);

        if (in_array($application->status, ['accepted', 'rejected'], true)) {
            return response()->json(['success' => false, 'message' => 'This application can no longer be withdrawn.'], 422);
        }

        $application->update(['status' => 'withdrawn']);

        return response()->json(['success' => true, 'message' => 'Application withdrawn.']);
    }

    private function authorizeOwner(Request $request, JobListing $job): void
    {
        abort_unless($job->posted_by === $request->user()->id, 403, 'Only the job owner can manage this listing.');
    }
}