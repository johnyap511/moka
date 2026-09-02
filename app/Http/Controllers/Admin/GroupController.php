<?php

namespace App\Http\Controllers\Admin;

use App\Group;
use Illuminate\Support\Facades\DB;
use App\Listing;
use App\OtherModel\GroupType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups = Group::all();

        // How many units sit in each group. Membership lives in listing_groups
        // and was not surfaced anywhere, so a group's contents could not be seen
        // at all from this screen.
        $counts = DB::table('listing_groups')
            ->selectRaw('group_id, COUNT(DISTINCT listing_id) c')
            ->groupBy('group_id')
            ->pluck('c', 'group_id');

        return view('admin.listing.group.index', compact('groups', 'counts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.listing.group.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->only("name","description");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:200',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $group = Group::create($data);
        $types = $request->type;
        $weights = $request->weight;
        if(is_array($types) && count($types) > 0){
            $i=0;
            foreach ($types as $type){
                if(strlen($type)>0 && array_key_exists($i,$weights)){
                    GroupType::create(['group_id'=>$group->id, 'type'=>$type, 'weight'=>$weights[$i]]);
                }
                $i++;
            }
        }
        return redirect('/admin/group')->with('success', 'Group is created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * The units in a group.
     *
     * Archived properties are included: a group's history should still show what
     * was in it, and hiding them would make the count on the index disagree with
     * the list here.
     */
    public function show($id)
    {
        $group = Group::findOrFail($id);

        $listingIds = DB::table('listing_groups')
            ->where('group_id', $group->id)
            ->pluck('listing_id')
            ->unique();

        $listings = Listing::withArchived()
            ->whereIn('id', $listingIds)
            ->orderBy('name')
            ->get();

        $types = GroupType::where('group_id', $group->id)->get();

        // Preloaded rather than one query per row.
        $owners = \App\User::whereIn('id', $listings->pluck('user_id')->filter())
            ->pluck('name', 'id');

        return view('admin.listing.group.show', compact('group', 'listings', 'types', 'owners'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $group = Group::find($id);
        return view('admin.listing.group.edit', compact('group'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->only("name","description");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:200',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $group = Group::find($id);
        $group->update($data);

        $gtypes = GroupType::where('group_id', $group->id)->get();
        foreach ($gtypes as $gtype){
            $t = $request->gtype[$gtype->id];
            $w = $request->gweight[$gtype->id];
            if(strlen($t)>0 && strlen($w)>0){
                $gtype->update(['type'=>$t,'weight'=>$w]);
            }else{
                GroupType::where('id', $gtype->id)->delete();
            }
        }

        $types = $request->type;
        $weights = $request->weight;
        if(is_array($types) && count($types) > 0){
            $i=0;
            foreach ($types as $type){
                if(strlen($type)>0 && array_key_exists($i,$weights)){
                    GroupType::create(['group_id'=>$group->id, 'type'=>$type,'weight'=>$weights[$i]]);
                }
                $i++;
            }
        }

        return redirect('/admin/group')->with('success', 'Group is updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
