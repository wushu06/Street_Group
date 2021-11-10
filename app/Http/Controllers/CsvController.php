<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;

class CsvController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('welcome');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        if (!$request->file) {
            return Redirect::back()->withErrors(['File not found or invalid!']);
        }
        $request->validate(['file' => 'required|mimes:csv']);
        $owners = $this->getHomeOwners($request);


        return Redirect::back()->with('success', [
            'data' => $owners
        ]);
    }

    /**
     * @param Request $request
     */
    protected function getHomeOwners(Request $request): array
    {
        $csv = [];
        if (($handle = fopen($request->file, 'r')) !== FALSE) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($row == 0) {
                    $row++;
                    continue;
                }
                $parsed = $this->parse($data[0]);
                if(!isset($parsed['title'])){
                    foreach ($parsed as $p){
                        $csv[$row] = $p;
                        $row++;
                    }
                }else{
                    $csv[$row] = $parsed;
                    $row++;
                }


            }
            fclose($handle);
        }

        return $csv;
    }

    /**
     * @param $owner
     * @return array
     */
    private function parse($owner): array
    {
        $result = [];
        $ownerArray = explode(' ', $owner);
        if (in_array("and", $ownerArray)) {
            return $this->parseConjunction( 'and', $owner, $ownerArray);
        }
        if (in_array("&", $ownerArray)) {
            return $this->parseConjunction( '&', $owner, $ownerArray);
        }
        $isInitial = strlen($ownerArray[1]) === 1 || strpos($ownerArray[1], ".") !== false ;
        $result['title'] = $ownerArray[0];
        $result['first_name'] = !$isInitial ? $ownerArray[1] : null;
        $result['initial'] = $isInitial ? $ownerArray[1] : null;
        $result['last_name'] = $ownerArray[2] ?? null;

        return $result;

    }

    /**
     * @param $conj
     * @param $owner
     * @param $ownerArray
     * @return array|array[]
     */
    private function parseConjunction($conj, $owner, $ownerArray): array
    {
        $result = [];
        $withoutAnd = explode(' '.$conj.' ', $owner);
        $position = array_search($conj, $ownerArray);
        $sameLastName = $position === 1;
        if($sameLastName){
            return [
                0 => [
                    'title' => $ownerArray[0],
                    'first_name' =>  null,
                    'initial' => null,
                    'last_name' => $ownerArray[3],
                ],
                1=> [
                    'title' => $ownerArray[2],
                    'first_name' =>  null,
                    'initial' => null,
                    'last_name' => $ownerArray[3],
                ]
            ];
        }

        foreach ($withoutAnd as $w) {
            $result[] = $this->parse($w);
        }
        return $result;
    }


}
