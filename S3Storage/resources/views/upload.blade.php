@extends('S3Storage::layouts.master')

@section('content')
    <div>
        <form class="api-request-form" action="#" method="post" enctype="multipart/form-data">
            <input type="hidden" name="extensions" value="{{ $extensionNames }}">
            <input type="hidden" name="maxSize" value="{{ $maxSize }}">
            <input type="hidden" name="maxDuration" value="{{ $maxDuration }}">

            <div class="input-group mb-3">
                <input type="file" class="form-control" name="files" accept="{{ $inputAccept }}" @if($maxUploadNumber > 1) multiple="multiple" max="{{ $maxUploadNumber }}" @endif>
                <button class="btn btn-outline-secondary" type="submit" id="uploadSubmit">{{ $fsLang['editorUploadButton'] }}</button>
            </div>

            <div class="progress d-none" id="progressbar" role="progressbar" aria-label="Animated striped example" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
            </div>
        </form>

        <div class="form-text mt-3 text-break">{{ $fsLang['editorUploadTipExtensions'] }}: {{ $extensionNames }}</div>

        <div class="form-text mt-2">{{ $fsLang['editorUploadTipMaxSize'] }}: {{ $maxSize }} MB</div>

        @if ($fileType == 'video' || $fileType == 'audio')
            <div class="form-text mt-2">{{ $fsLang['editorUploadTipMaxDuration'] }}: {{ $maxDuration }} {{ $fsLang['unitSecond'] }}</div>
        @endif

        <div class="form-text mt-2">{{ $fsLang['editorUploadTipMaxNumber'] }}: {{ $maxUploadNumber }}</div>
    </div>
@endsection

@push('script')
    <script>
        var numberFiles = 0;
        var numberUploaded = 0;

        // request
        $('.api-request-form').submit(function (e) {
            e.preventDefault();
            let form = $(this),
                files = form.find('input[name=files]')[0].files;

            numberFiles = files.length;

            Array.from(files).forEach(file => {
                getFileData(file);
            });
        });

        // get file data
        function getFileData(file) {
            let fileType = "{{ $fileType }}";

            function proceedWithUpload(fileType, fileData) {
                console.log('fileData', fileType, fileData);

                if (!validateFile(fileData)) {
                    return;
                }

                getUploadToken(fileData, file);
            }

            let fileData = {
                name: file.name,
                mime: file.type,
                extension: file.name.split('.').pop(),
                size: file.size,
                width: null,
                height: null,
                duration: null,
                warning: null,
                sortOrder: null,
                moreInfo: null,
            };

            if (fileType == 'image') {
                const image = new Image();
                image.onload = function() {
                    fileData.width = this.naturalWidth;
                    fileData.height = this.naturalHeight;
                    URL.revokeObjectURL(this.src);  // Clean up memory

                    // upload file
                    proceedWithUpload(fileType, fileData);
                };
                image.onerror = function() {
                    console.error("Error loading image");
                };
                image.src = URL.createObjectURL(file);

                return;
            }

            if (fileType == 'video' || fileType == 'audio') {
                const media = document.createElement(fileType);
                media.preload = 'metadata';
                media.onloadedmetadata = function() {
                    fileData.duration = Math.round(media.duration);
                    URL.revokeObjectURL(this.src);  // Clean up memory

                    // upload file
                    proceedWithUpload(fileType, fileData);
                };
                media.onerror = function() {
                    console.error(`Error loading ${fileType}`);
                };
                media.src = URL.createObjectURL(file);

                return;
            }

            // upload file
            proceedWithUpload(fileType, fileData);
        };

        // validate file data
        function validateFile(fileData) {
            let extensions = $('input[name="extensions"]').val().split(','),
                maxSize = parseInt($('input[name="maxSize"]').val()),
                maxDuration = parseInt($('input[name="maxDuration"]').val());

            let fileName = fileData.name;
            let fileExtension = fileData.extension;
            let fileSize = fileData.size;
            let fileDuration = fileData.duration;
            let tipMessage;

            if (!extensions.includes(fileExtension)) {
                tipMessage = '[' + fileName + "] {{ $fsCodeMessage['36310'] }}";
                tips(tipMessage, true);

                return false;
            }

            if (fileSize > maxSize * 1024 * 1024) {
                tipMessage = '[' + fileName + "] {{ $fsCodeMessage['36113'] }}";
                tips(tipMessage, true);

                return false;
            }

            if (maxDuration && fileDuration > maxDuration) {
                tipMessage = '[' + fileName + "] {{ $fsCodeMessage['36114'] }}";
                tips(tipMessage, true);

                return false;
            }

            return true;
        }

        // get upload token
        function getUploadToken(fileData, file) {
            $.ajax({
                url: "{{ route('s3-storage.api.upload-token') }}",
                type: "GET",
                data: fileData,
                success: function (res) {
                    if (res.code != 0) {
                        tips(res.message, true);

                        let btn = $('#uploadSubmit').find('button[type="submit"]');
                        btn.prop('disabled', false);
                        btn.find('.spinner-border').remove();

                        return;
                    }

                    uploadFile(res.data, file);
                },
            });
        };

        // upload file
        function uploadFile(uploadToken, file) {
            let formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: uploadToken.url,
                type: uploadToken.method,
                headers: uploadToken.headers,
                data: formData,
                processData: false,
                contentType: false,
                enctype: 'multipart/form-data',
                success: function (res) {
                    numberUploaded++;

                    let windowClose = numberFiles == numberUploaded;

                    console.log('updateFileUploaded', uploadToken.fid, windowClose, numberFiles, numberUploaded);

                    updateFileUploaded(uploadToken.fid, windowClose);
                },
                error: function (e) {
                    tips("{{ $fsCodeMessage['32105'] }}", true);

                    let btn = $('#uploadSubmit').find('button[type="submit"]');
                    btn.prop('disabled', false);
                    btn.find('.spinner-border').remove();
                },
            });
        };

        // update file uploaded
        function updateFileUploaded(fid, windowClose = false) {
            $.ajax({
                url: "{{ route('s3-storage.api.uploaded') }}",
                type: 'patch',
                data: {
                    fid: fid,
                },
                success: function (res) {
                    if (res.code != 0) {
                        tips(res.message, true);
                        return;
                    }

                    // postMessage
                    let callbackAction = {
                        postMessageKey: '{{ $postMessageKey }}',
                        windowClose: windowClose,
                        redirectUrl: '',
                        dataHandler: 'add',
                    };

                    // /static/js/fresns-callback.js
                    FresnsCallback.send(callbackAction, res.data);
                },
                complete: function (e) {
                    if (windowClose) {
                        let btn = $('#uploadSubmit').find('button[type="submit"]');
                        btn.prop('disabled', false);
                        btn.find('.spinner-border').remove();
                    }
                },
            });
        };
    </script>
@endpush
