<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Modules\Academics\Models\Classroom;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassroomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_a_classroom_with_a_unique_code(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);

        $response = $this->actingAs($teacher)->post(route('teacher.classrooms.store'), [
            'name' => 'الرياضيات - الصف الأول',
            'description' => 'صف تجريبي',
        ]);

        $classroom = Classroom::query()->where('teacher_id', $teacher->id)->firstOrFail();

        $response->assertRedirect(route('teacher.classrooms.show', $classroom));
        $this->assertSame(6, strlen($classroom->code));
        $this->assertSame('الرياضيات - الصف الأول', $classroom->name);
    }

    public function test_classroom_creation_requires_a_name(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);

        $response = $this->actingAs($teacher)->post(route('teacher.classrooms.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('classrooms', 0);
    }

    public function test_a_teacher_cannot_view_another_teachers_classroom(): void
    {
        $owner = User::factory()->teacher()->create(['is_approved' => true]);
        $stranger = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($owner, 'teacher')->create();

        $this->actingAs($stranger)->get(route('teacher.classrooms.show', $classroom))->assertForbidden();
        $this->actingAs($stranger)->get(route('teacher.classrooms.edit', $classroom))->assertForbidden();
        $this->actingAs($stranger)
            ->put(route('teacher.classrooms.update', $classroom), ['name' => 'محاولة تعديل'])
            ->assertForbidden();
        $this->actingAs($stranger)->delete(route('teacher.classrooms.destroy', $classroom))->assertForbidden();

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'name' => $classroom->name]);
    }

    public function test_deleting_a_classroom_without_exams_hard_deletes_it(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->delete(route('teacher.classrooms.destroy', $classroom))
            ->assertRedirect(route('teacher.classrooms.index'));

        $this->assertDatabaseMissing('classrooms', ['id' => $classroom->id]);
    }

    public function test_deleting_a_classroom_with_exams_archives_it_instead(): void
    {
        $teacher = User::factory()->teacher()->create(['is_approved' => true]);
        $classroom = Classroom::factory()->for($teacher, 'teacher')->create();

        DB::table('exams')->insert([
            'code' => 'EX-0001',
            'title' => 'اختبار تجريبي',
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->delete(route('teacher.classrooms.destroy', $classroom))
            ->assertRedirect(route('teacher.classrooms.index'));

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'is_archived' => true]);
    }
}
