<?php

namespace App\Http\Controllers;

use App\GameType;
use App\Http\Requests\CreatePoolRequest;
use App\Pool;
use App\PoolMode;
use App\Tournament;
use Illuminate\Http\Request;

class PoolController extends Controller
{
  public function create(Request $request, Tournament $tournament)
  {
    $poolModes = PoolMode::all();
    $gameTypes = GameType::all();
    return view('pools.create')->with(compact('poolModes', 'gameTypes', 'tournament'));
  }

  public function store(CreatePoolRequest $request, Tournament $tournament)
  {
    $pool = new Pool();
    $pool->fill($request->all() + ['tournament_id' => $tournament->id]);
  }
}
