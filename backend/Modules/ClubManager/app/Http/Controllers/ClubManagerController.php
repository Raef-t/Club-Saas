<?php

namespace Modules\ClubManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClubManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('clubmanager::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('clubmanager::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('clubmanager::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('clubmanager::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
