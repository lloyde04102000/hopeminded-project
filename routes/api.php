<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CheckAccessIdController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\QuizReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TextbookController;
use App\Http\Controllers\TextbookFlagController;
use App\Http\Controllers\UserController;
use App\Models\Teacher;
use App\Models\Textbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/register', [RegisterController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return Auth::user();
    });
    Route::post('/textbook/flag/add', [TextbookFlagController::class, 'storeFlags']);
    Route::get('/users/students/get', [UserController::class, 'getStudents']);
    Route::post('/users/students/add', [UserController::class, 'addStudent']);

    Route::get('/users/teachers/get', [TeacherController::class, 'getTeachers']);
    Route::post('/users/teachers/add', [TeacherController::class, 'addTeacher']);

    Route::get('flags/{flag}', [TextbookFlagController::class, 'getFlags']);

    Route::put('update-flag/{flag}', [TextbookFlagController::class, 'updateFlag']);

    Route::post('quiz/info/store/{flag}', [GameController::class, 'storeGameInfo']);
    Route::get('quiz/info/get', [GameController::class, 'getGameInfo']);
    Route::post('quiz/info/update/{id}', [GameController::class, 'updateGameInfo']);

    Route::get('quiz/reports/get', [GameController::class, 'getQuizReports']);

    Route::get('quiz-reports/get', [GameController::class, 'allGameRecords']);

    Route::get('/certificates/student/{studentId}/game-id/{gameFlag}', [CertificateController::class, 'getCertificates']);
    Route::post('/certificate/upload', [CertificateController::class, 'uploadFile']);
    Route::post('/certificate/delete', [CertificateController::class, 'deleteCertificate']);
    Route::get('/student-certificates/all', [CertificateController::class, 'getStudentCertificates']);

    Route::post('/textbook/add', [TextbookController::class, 'addTextbook']);
    Route::post('/textbook/alphabets-words/add', [TextbookController::class, 'addTextbookAlphabetWords']);
    Route::delete('textbook/delete/{textbookId}', [TextbookController::class, 'deleteTextbook']);

    Route::get('quiz-report/student/{studentId}', [QuizReportController::class, 'getStudentQuizReport']);

    Route::get('alphabets-letters/get', [TextbookController::class, 'getAlphabetsLetters']);
    Route::get('vowels-consonants/get', [TextbookController::class, 'getVowelsConsonants']);
    Route::get('alphabets-words/get', [TextbookController::class, 'getAlphabetsWords']);

    Route::get('students-of-teacher/{teacherId}', [TeacherController::class, 'getStudents']);

    Route::get('profile/get', [StudentController::class, 'getProfile']);
    Route::post('profile/update', [StudentController::class, 'updateProfile']);

    Route::post('/logout', [LogoutController::class, 'logout']);

    Route::post('seed', function () {
        Textbook::query()->delete();
        $teacherId = Teacher::where('user_id', Auth::user()->id)->first()->id;
        $jsonFile = Storage::path('public/json/alphabets-with-letters.json');
        $jsonData = json_decode(file_get_contents($jsonFile), true);

        foreach ($jsonData as $data) {
            if (in_array($data["letter"], ['a', 'e', 'i', 'o', 'u']))
                $type = 'vowel';
            else
                $type = 'consonant';

            Textbook::updateOrCreate([
                'flag' => 'alphabet-letters',
                'type' => $type,
                'teacher_id' => $teacherId,
                'letter' => $data["letter"],
                'object' => $data["object"],
                'image' => json_encode($data["image"]),
                'video' => json_encode($data["video"]),
                'chapter' => 1
            ]);
        }

        $jsonFile = Storage::path('public/json/vowel-consonants.json');
        $jsonData = json_decode(file_get_contents($jsonFile), true);

        foreach ($jsonData as $json) {
            foreach ($json as $data) {
                if (in_array($data["letter"], ['a', 'e', 'i', 'o', 'u']))
                    $type = 'vowel';
                else
                    $type = 'consonant';

                Textbook::updateOrCreate([
                    'flag' => 'vowel-consonants',
                    'type' => $type,
                    'teacher_id' => $teacherId,
                    'letter' => $data["letter"],
                    'object' => $data["object"],
                    'image' => json_encode($data["image"]),
                    'video' => json_encode($data["video"]),
                    'chapter' => 1
                ]);
            }
        }

        $jsonFile = Storage::path('public/json/alphabets-with-words.json');
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        foreach ($jsonData as $json) {
            foreach ($json as $key => $data) {
                $object = [];
                $image = [];
                $video = [];
                foreach ($data as $key2 => $data2) {
                    $object[] = $data2['object'];
                    $image[] = $data2['image'];
                    $video[] = $data2['video'];
                }
                if (in_array($key, ['a', 'e', 'i', 'o', 'u']))
                    $type = 'vowel';
                else
                    $type = 'consonant';
                Textbook::updateOrCreate([
                    'flag' => 'alphabet-words',
                    'type' => $type,
                    'teacher_id' => $teacherId,
                    'letter' => $key,
                    'object' => json_encode($object),
                    'image' => json_encode($image),
                    'video' => json_encode($video),
                    'chapter' => 1
                ]);
            }
        }

        return response()->json([
            'message' => 'Textbooks are successfully inserted.'
        ]);
    });
});
Route::post('reset-password/send-link', [EmailController::class, 'sendResetPasswordLink']);
Route::get('/check-access-id', [CheckAccessIdController::class, 'checkAccessId']);
Route::get('/reset-password/form', function() {
    return redirect('/reset-password');
});

Route::post('/reset-password', [EmailController::class, 'resetPassword']);
