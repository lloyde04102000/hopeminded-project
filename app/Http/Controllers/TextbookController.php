<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlphabetRequest;
use App\Http\Requests\TextbookRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Textbook;
use App\Rules\UniqueDependingOnFlag;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Laravel\Ui\Presets\React;

class TextbookController extends Controller
{
    public function getVowelsConsonants(Request $request)
    {
        if ($request->get('user') == 'student') {
            $teacherId = Student::where('user_id', Auth::user()->id)->first()->teacher_id;
        } else {
            $teacherId = Teacher::where('user_id', Auth::user()->id)->first()->id;
        }
        $textbooks = Textbook::where('teacher_id', $teacherId)
            ->where('flag', 'vowel-consonants')
            ->get()
            ->map(function ($textbooks) {
                $textbooks->image_url = Storage::url(json_decode($textbooks->image));
                $textbooks->video_url = Storage::url(json_decode($textbooks->video));
                return $textbooks;
            })->toArray();

        $vowels = array_filter($textbooks, function ($textbook) {
            return $textbook["type"] == 'vowel';
        });
        $consonants = array_filter($textbooks, function ($textbook) {
            return $textbook["type"] == 'consonant';
        });
        return [array_values($vowels), array_values($consonants)];
    }

    public function getAlphabetsLetters(Request $request)
    {
        if ($request->get('user') == 'student') {
            $teacherId = Student::where('user_id', Auth::user()->id)->first()->teacher_id;
        } else {
            $teacherId = Teacher::where('user_id', Auth::user()->id)->first()->id;
        }
        return Textbook::where('teacher_id', $teacherId)
            ->where('flag', 'alphabet-letters')
            ->get()
            ->map(function ($textbooks) {
                $textbooks->image_url = Storage::url(json_decode($textbooks->image));
                $textbooks->video_url = Storage::url(json_decode($textbooks->video));
                return $textbooks;
            })->toArray();
    }

    public function addTextbook(TextbookRequest $request)
    {
        if (self::isLetterExist()) {
            return response()->json([
                'errors' => [
                    'letter' => ["This letter already exist."]
                ]
            ], 422);
        } else if (!self::isFirstLetterMatch()) {
            return response()->json([
                'errors' => [
                    'objectName' => ["The first letter of the object name and the entered letter did not match."]
                ]
            ], 422);
        }

        if ($request->has('image') && $request->has('video')) {
            if ($request->flag == 'alphabet-letters') {
                $storagePath = 'alphabets-letters';
            } else if ($request->flag == 'vowel-consonants') {
                $storagePath = 'vowels-consonants';
            } else {
                $storagePath = 'alphabets-words';
            }
            $imageFile = $request->file('image');
            $imageFileName = $imageFile->getClientOriginalName();
            $imagePath = $imageFile->storeAs(`{$storagePath}/images`, $imageFileName, 'public');

            $videoFile = $request->file('video');
            $videoFileName = $videoFile->getClientOriginalName();
            $videoPath = $videoFile->storeAs(`{$storagePath}/videos`, $videoFileName, 'public');

            $alphabetType = in_array($request->letter, ['a', 'e', 'i', 'o', 'u']) ? 'vowel' : 'consonant';

            $teacherId = Teacher::where('user_id', Auth::user()->id)->first()->id;
            Textbook::create([
                'flag' => $request->flag,
                'letter' => strtolower($request->letter),
                'object' => $request->objectName,
                'image' => json_encode($imagePath),
                'video' => json_encode($videoPath),
                'type' => $alphabetType,
                'teacher_id' => $teacherId
            ]);

            return response()->json(['message' => 'An alphabet is added successfully.']);
        }
    }

    public function addTextbookAlphabetWords(AlphabetRequest $request)
    {
        
    }

    public function isLetterExist()
    {
        $count = Textbook::where('flag', request()->flag)
            ->where('letter', request()->letter)
            ->count();

        return $count > 0;
    }

    public function isFirstLetterMatch()
    {
        $letter = request()->letter;
        $firstLetter = request()->objectName[0];

        return $firstLetter === $letter;
    }
}