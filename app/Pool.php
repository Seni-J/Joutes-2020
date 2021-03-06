<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Pool extends Model
{
    public $timestamps = false;

    protected $fillable = [
      'start_time', 'end_time', 'poolName', 'stage', 'poolSize', 'tournament_id', 'mode_id', 'game_type_id'
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
    public function contenders()
    {
        return $this->hasMany(Contender::class);
    }

    public function games(){
        return $this->hasManyThrough(Game::class, Contender::class, 'pool_id', 'contender1_id');
    }

    public function poolsInPreviousStage()
    {
        $pools = DB::table('pools')
            ->where('tournament_id', '=', $this->tournament->id)
            ->where('stage', '=', $this->stage - 1)
            ->get();
        return $pools;
    }

    public function rankings() {
        $teams = $this->listTeams();
        $games = $this->games;
        $rankings = array();


        foreach ($games as $key=>$game) {


            // for classement ----------------------------------------------------------
            if (!empty($teams)) {
                foreach ($teams as $id => $team) {
                    $score 		 = 0;
                    $win 		 = 0;
                    $loose       = 0;
                    $draw 	     = 0;
                    $goalBalance = 0;


                    $position = -1;
                    // check if team is already in ranking
                    foreach ($rankings as $key => $ranking) {
                        if ($ranking["team_id"] == $id) {
                            $position = $key;
                            break;
                        }
                    }
                    // if so get old ranking values
                    if ($position != -1) {
                        $score 			= $rankings[$position]["score"];
                        $win 			= $rankings[$position]["W"];
                        $loose 			= $rankings[$position]["L"];
                        $draw 			= $rankings[$position]["D"];
                        $goalBalance 	= $rankings[$position]["+-"];
                    }


                    if ((!empty($game->score_contender1) || !empty($game->score_contender2)) && !empty($game->contender1->team) && !empty($game->contender2->team)) {
                        if($game->contender1->team->name == $team || $game->contender2->team->name == $team) {
                            // $team had a draw
                            if($game->score_contender1 == $game->score_contender2) {
                                $score += 1;
                                $draw++;
                            }
                            // $team won the game
                            else if($game->score_contender1 > $game->score_contender2 && $game->contender1->team->name == $team ||
                                $game->score_contender2 > $game->score_contender1 && $game->contender2->team->name == $team) {
                                $score += 2;
                                $win++;
                            }
                            // $team lost the game
                            else {
                                $loose++;
                            }

                            // calcul the balance between goal+ ($team) and goal- (contender)
                            if($game->contender1->team->name == $team) {
                                $goalBalance += $game->score_contender1;
                                $goalBalance -= $game->score_contender2;
                            }
                            else if($game->contender2->team->name == $team) {
                                $goalBalance += $game->score_contender2;
                                $goalBalance -= $game->score_contender1;
                            }
                        }
                    }

                    if ($position == -1) {
                        $rankings[] = array(
                            "team_id" 	=> $id,
                            "team" 		=> $team,
                            "score" 	=> $score,
                            "W" 		=> $win,
                            "L" 		=> $loose,
                            "D" 		=> $draw,
                            "+-" 		=> $goalBalance
                        );
                    }
                    else {
                        $rankings[$position] = array(
                            "team_id" 	=> $id,
                            "team" 		=> $team,
                            "score" 	=> $score,
                            "W" 		=> $win,
                            "L" 		=> $loose,
                            "D" 		=> $draw,
                            "+-" 		=> $goalBalance
                        );
                    }
                }
            }
        }
        $rankings = sizeof($rankings) > 0 ? $this->sort($rankings) : array();
        return $rankings;
    }

    public function teams(){
        $teams = DB::table('contenders')
            ->where('pool_id', '=', $this->id)
            ->get();
        return $teams;
    }

    public function listTeams() {
      $teams = array();
        foreach ($this->games as $game) {
            if(!empty($game->contender1->team)){
                $teams[$game->contender1->team->id] = $game->contender1->team->name;
            }
            if(!empty($game->contender2->team)){
                $teams[$game->contender2->team->id] = $game->contender2->team->name;
            }
        }
        return $teams;
    }

    private function sort($rankings_row){
        $rankings_sort = array();
        foreach($rankings_row as $key=>$value) {
            $rankings_sort['score'][$key] = $value['score'];
            $rankings_sort['+-'][$key] = $value['+-'];
        }
        array_multisort($rankings_sort['score'], SORT_DESC, $rankings_sort['+-'], SORT_DESC, $rankings_row);

        return $rankings_row;
    }

    public function isEditable(){
        if(Auth::check()){
            $role = Auth::user()->role;
            if($role == "writer" || $role == "administrator") return ($this->isFinished == 0);
        }
        return false;
    }

}
