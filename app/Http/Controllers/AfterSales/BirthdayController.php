<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Client;
use Carbon\Carbon;

class BirthdayController extends Controller
{
    public function index(Request $request)
    {
        $currentMonth = Carbon::now()->month;
        $currentDay = Carbon::now()->day;

        // Buscar clientes do mês atual
        $clients = Client::whereMonth('data_nascimento', $currentMonth)
            ->orderByRaw('DAY(data_nascimento) ASC')
            ->get();

        $todayBirthdays = [];
        $upcomingBirthdays = [];
        $pastBirthdays = [];

        foreach ($clients as $client) {
            $birthDate = Carbon::parse($client->data_nascimento);
            
            $clientData = [
                'id' => $client->id,
                'nome' => $client->nome,
                'email' => $client->email,
                'celular1' => $client->celular1,
                'data_nascimento' => $birthDate->format('d/m/Y'),
                'dia' => $birthDate->day,
                'idade' => Carbon::now()->year - $birthDate->year
            ];

            if ($birthDate->day == $currentDay) {
                $todayBirthdays[] = $clientData;
            } elseif ($birthDate->day > $currentDay) {
                $upcomingBirthdays[] = $clientData;
            } else {
                $pastBirthdays[] = $clientData;
            }
        }

        return Inertia::render('AfterSales/Birthdays/Index', [
            'todayBirthdays' => $todayBirthdays,
            'upcomingBirthdays' => $upcomingBirthdays,
            'pastBirthdays' => $pastBirthdays,
            'currentMonthName' => Carbon::now()->translatedFormat('F')
        ]);
    }
}
