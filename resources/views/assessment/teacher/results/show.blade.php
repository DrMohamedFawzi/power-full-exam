<x-layouts.app :title="$exam->title">
    <x-slot:header>
        <x-ui.page-header :title="'نتائج: '.$exam->title" :breadcrumbs="['التقارير والنتائج' => route('teacher.results.index'), $exam->title => null]" />
    </x-slot:header>

    <div class="flex flex-col gap-6">
        @include('assessment.teacher.partials.results-table', ['sessions' => $results['sessions']])
        @include('assessment.teacher.partials.question-stats', ['stats' => $results['question_stats']])
        @include('assessment.teacher.partials.grading-queue', ['pendingGrading' => $pendingGrading])
    </div>
</x-layouts.app>
