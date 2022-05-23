<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $persons = Person::all();
        if (empty($persons)) {
            return response()->json([], 204);
        }

        $output = [];
        $now = $request->input('date') ? new Carbon($request->input('date')) : Carbon::now();

        foreach ($persons as $person) {
            $dob = $person->birthdate;

            // Determine next occurrence by seeing if the month/day has already passed this year
            $nextBirthday = clone $dob;
            $nextBirthday->year = $now->year;
            if ($dob->month < $now->month
                || ($dob->month === $now->month && $dob->day < $now->day)
            ) {
                $nextBirthday->year++;
            }

            // Calculate age at next bday
            $age = $nextBirthday->year - $dob->year;

            // Is today the birthday?
            $isBirthday = $dob->month === $now->month && $dob->day === $now->day;

            if ($isBirthday) {
                // Get interval between now and tomorrow in timezone
                $nowTz = clone $now;
                $nowTz->tz = $person->timezone;
                $tomorrow = clone $nowTz;
                $tomorrow->day++;
                $tomorrow->hour = 0;
                $tomorrow->minute = 0;
                $tomorrow->second = 0;
                $interval = $nowTz->diffAsCarbonInterval($tomorrow);

                $when = 'today (' . $interval->h . ' hours remaining in ' . $person->timezone . ')';
            } else {
                // Get interval between now and next bday occurrence
                $interval = $now->diffAsCarbonInterval($nextBirthday);

                $when = 'in ' . $interval->forHumans(['skip' => ['h', 'min', 's'], 'join' => ', '])
                . ' in ' . $person->timezone;
            }

            $output[] = [
                '_id' => $person->_id,
                'name' => $person->name,
                'birthdate' => $person->birthdate->format('Y-m-d'),
                'timezone' => $person->timezone,
                'isBirthday' => $isBirthday,
                'interval' => [
                    'y' => $interval->y,
                    'm' => $interval->m,
                    'd' => $interval->d,
                    'h' => $interval->h,
                    'i' => $interval->i,
                    's' => $interval->s,
                ],
                'message' => $person->name . ' is ' . $age . ' years old ' . $when,
            ];
        }
        return response()->json(['data' => $output]);
    }

    /**
     * Create a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        try {
            $person = Person::create([
                'name' => $request->input('name'),
                'birthdate' => new Carbon(
                    $request->input('birthdate'),
                    new DateTimeZone($request->input('timezone'))
                ),
                'timezone' => $request->input('timezone'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        $person->save();
        return response()->json(['id' => $person->_id]);
    }

    /**
     * Fetch a specific user.
     *
     * @param $id
     * @return JsonResponse
     */
    public function get($id)
    {
        $person = Person::find($id);
        if ($person) {
            return response()->json($person);
        }
        return response()->json(false, 404);
    }

    /**
     * Delete a specific user.
     *
     * @param $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        $person = Person::find($id);
        if ($person && $person->delete()) {
            return response()->json($person && $person->delete());
        }
        return response()->json(false, 404);
    }
}
