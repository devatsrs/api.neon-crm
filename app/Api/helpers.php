<?php
function check_date_format_db($date){
    $datefomated = date('Y-m-d H:i:s',strtotime($date));
    if(date('Y', strtotime($datefomated)) == '1970'){
        throw new Exception('Invalid Date Format!!');
    }
    return $datefomated;
}
function uploaded_File_Handler($fileArray){
    $files_array = [];
    foreach($fileArray as $Index=>$file){
        $decoded_file = base64_decode($file['file']);
        $fileName = $file['fileName'];
        $tempPath = getenv('TEMP_PATH');
        $stamp = date_timestamp_get(date_create());
        $path = $tempPath.'/'.$stamp.$fileName;
        file_put_contents($path, $decoded_file);
        $files_array[] = ['fileName'=>$file['fileName'],
                            'file'=>$path,
                            'Extension'=>$file['fileExtension']];
    }
    return $files_array;
}
function rename_win($oldfile,$newfile) {
    //if (!rename($oldfile,$newfile)) {
        if (copy ($oldfile,$newfile)) {
            unlink($oldfile);
            return TRUE;
        }
        //return FALSE;
    //}
    return TRUE;
}