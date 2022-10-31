@extends('QiNiu::layouts.master')

@section('content')
    <form class="my-2 mx-2" id="QiNiuForm" method="post" action="http://upload.qiniup.com/" enctype="multipart/form-data">
        <input type="hidden" name="type" value="{{ $fileType }}">
        <input type="hidden" name="platformId" value="{{ $checkHeaders['platformId'] }}">
        <input type="hidden" name="aid" value="{{ $checkHeaders['aid'] }}">
        <input type="hidden" name="uid" value="{{ $checkHeaders['uid'] }}">
        <input type="hidden" name="usageType" value="{{ $uploadInfo['usageType'] }}">
        <input type="hidden" name="tableName" value="{{ $uploadInfo['tableName'] }}">
        <input type="hidden" name="tableColumn" value="{{ $uploadInfo['tableColumn'] ?? 'id' }}">
        <input type="hidden" name="tableId" value="{{ $uploadInfo['tableId'] ?? null }}">
        <input type="hidden" name="tableKey" value="{{ $uploadInfo['tableKey'] ?? null }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <input type="hidden" name="uploadType" value="{{ $uploadInfo['type'] }}">
        <input type="hidden" name="uploadToken" value="{{ $uploadToken }}">

        <div class="input-group">
            <input class="form-control" type="file" @if($uploadConfig['uploadNumber'] > 1) multiple="multiple" @endif id="formFile" accept="{{ $uploadConfig['inputAccept'] }}">
            <button class="btn btn-outline-secondary" type="button" onclick="uploadFiles(event)">{{ $fsLang['editorUploadBtn'] }}</button>
        </div>
    </form>
    <div class="mx-2 mt-3 text-secondary fs-7">{{ $fsLang['editorUploadExtensions'] }}: {{ $uploadConfig['extensions'] }}</div>
    <div class="mx-2 mt-2 text-secondary fs-7">{{ $fsLang['editorUploadMaxSize'] }}: {{ $uploadConfig['maxSize'] }} MB</div>
    @if ($uploadConfig['maxTime'] > 0)
        <div class="mx-2 mt-2 text-secondary fs-7">{{ $fsLang['editorUploadMaxTime'] }}: {{ $uploadConfig['maxTime'] }} {{ $fsLang['unitSecond'] }}</div>
    @endif
    <div class="mx-2 my-2 text-secondary fs-7">{{ $fsLang['editorUploadNumber'] }}: {{ $uploadConfig['uploadNumber'] }}</div>
@endsection
