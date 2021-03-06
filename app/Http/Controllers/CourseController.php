<?php

namespace App\Http\Controllers;

use App\Course;
use App\Type;
use App\Edition;
use App\Term;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Auth;

class CourseController extends Controller
{

    public function index()
    {
        //Sort with own courses first
        $courses = Course::allWithMineOnTop();

        return view('courses.index')
            ->with(compact('courses'));
    }

    public function create()
    {
        $available = $tags = Tag::all();

        return view('courses.form')
            ->with('course', new Course())
            ->with(compact('tags'))
            ->with(compact('available'))
            ->with('types', Type::all());
    }

    public function store(Request $request)
    {
        $this->validate(request(), [
            'title' => 'required|string',
            'type_id' => 'required|integer|min:1',
            'owner' => 'required|alpha_dash',
            'link' => 'nullable|url',
            'order' => 'nullable|integer',
            'image' => 'nullable|image',
        ]);

        $course = new Course();
        $course->title = $request->title;
        $course->type_id = $request->type_id;
        $course->owner = $request->owner;
        $course->link = $request->link;
        $course->order = $request->order ?? 0;
        $course->description = $request->description;

        if($request->hasFile('image'))
        {
            $extension = $request->image->getClientOriginalExtension();
            $filename = 'asset_course_' . uniqid() . '.' . $extension;
            $path = Storage::disk('public')->putFileAs('uploads/assets', $request->image, $filename);
            $course->image = $path;
        }

        $course->save();
        $course->tags()->sync($request->tags);

        return redirect()->route('courses.show', $course);
    }

    public function show(Course $course)
    {
        $terms = $course->terms;
        return view('courses.show')
            ->with(compact('terms'))
            ->with(compact('course'))
            ->with('edition', null);
    }

    public function show_edition(Course $course, Edition $edition)
    {
        $terms = $course->terms->sortByDesc(function($term, $key) use ($edition){
            return (int)($term->pivot->id == $edition->id);
        })->values();

        return view('courses.show')
            ->with(compact('course'))
            ->with(compact('terms'))
            ->with(compact('edition'));
    }

    public function edit(Course $course)
    {
        $tags = Tag::all();
        $available = $tags->reject(function ($tag, $key) use ($course) {
            return $course->tags->contains($tag);
        });

        return view('courses.form')
            ->with(compact('course'))
            ->with(compact('tags'))
            ->with(compact('available'))
            ->with('types', Type::all());
    }

    public function update(Request $request, Course $course)
    {
        $this->validate(request(), [
            'title' => 'required|string',
            'type_id' => 'required|integer|min:1',
            'owner' => 'required|alpha_dash',
            'link' => 'nullable|url',
            'order' => 'nullable|integer',
            'image' => 'nullable|image',
        ]);

        $course->title = $request->title;
        $course->type_id = $request->type_id;
        $course->owner = $request->owner;
        $course->link = $request->link;
        $course->order = $request->order ?? 0;
        $course->description = $request->description;

        if($request->hasFile('image'))
        {
            $extension = $request->image->getClientOriginalExtension();
            $filename = 'asset_course_' . uniqid() . '.' . $extension;
            $path = Storage::disk('public')->putFileAs('uploads/assets', $request->image, $filename);
            $course->image = $path;
        }
        
        $course->save();
        $course->tags()->sync($request->tags);

        return redirect()->route('courses.show', $course);
    }

    public function delete(Course $course)
    {
        return view('courses.delete')
            ->with(compact('course'));
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('courses.index');
    }

}
