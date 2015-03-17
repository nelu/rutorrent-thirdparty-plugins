<?php
namespace Flm;

class FsUtils {

    public static $format_methods = array(
        'tar.gz' => 'tgzExtractCmd',
        'gz' => 'tgzExtractCmd',
        'tgz' => 'tgzExtractCmd',
        'zip' => 'zipExtractCmd',
        'rar' => 'rarExtractCmd',
        'bzip2' => 'bzipExtractCmd'
    );

    public static function getCopyCmd($source, $to) {
        $source = escapeshellarg($source);
        $to = escapeshellarg($to);
        return <<<CMD
cp -rf {$source} {$to} 
CMD;

    }

    public static function getRemoveCmd($file) {
        $file = escapeshellarg($file);
        return <<<CMD
rm -rf {$file} 
CMD;

    }

    public static function getArchiveCompressCmd($args) {

        $params = clone $args;
        $format_methods = self::$format_methods;

        $cmd = false;

        $ext = pathinfo($params->archive, PATHINFO_EXTENSION);

        var_dump('Archive extension', $ext);

        if (isset($format_methods[$ext])) {

            $method_name = str_replace('Extract', 'Compress', $format_methods[$ext]);
            $cmd = call_user_func_array(array(
                __CLASS__,
                $method_name
            ), array($params));
            // var_dump($cmd);

        }

        return $cmd;
    }
    
    public static function ffmpegScreensheetCmd($params) {
        
        $options = $params->options;
        $video_file = Helper::mb_escapeshellarg($params->file);
        $screenfile = Helper::mb_escapeshellarg($params->imgfile);

        var_dump(__METHOD__, $params);
        
        $filters = //'drawtext="timecode=\'00\:00\:00\:00\' :rate=24 :fontcolor=white :fontsize=21 :shadowcolor=black :x=5 :y=5",' .
                    'scale="min('. $options->scwidth. '\, iw*3/2):-1",' .
                    'select="not(mod(n\,' . $options->frame_step . ')),tile=' . $options->scrows. 'x'. $options->sccols .'"';

        return <<<CMD
{$params->binary} -i {$video_file} -an -vf {$filters} -vsync 0 -frames:v 1 {$screenfile} 2>&1 | sed -u 's/^/0:  /'
CMD;
    }
    

    public static function getArchiveExtractCmd($args) {

        $params = clone $args;
        $format_methods = self::$format_methods;

        $cmd = false;

        $ext = pathinfo($params->file, PATHINFO_EXTENSION);

        var_dump('Archive extension', $ext);

        if (isset($format_methods[$ext])) {

            $method_name = $format_methods[$ext];
            $cmd = call_user_func_array(array(
                __CLASS__,
                $method_name
            ), array($params));
            // var_dump($cmd);

        }

        return $cmd;
    }

    public static function zipCompressCmd($params) {

        // $paths = Helper::escapeCmdArgs($params);

        $options = $params->options;
        $files = implode(' ', (array)Helper::escapeCmdArgs($params->files));
        $archive = Helper::mb_escapeshellarg($params->archive);

        var_dump(__METHOD__, $params);

        return <<<CMD
{$params->binary} -r {$options->compression} -y {$archive} {$files} 2>&1 | sed -u 's/^/0: /'
CMD;
    }

    public static function zipExtractCmd($params) {

        $paths = Helper::escapeCmdArgs($params);

        var_dump(__METHOD__, $params);

        return <<<CMD
{$paths->binary} -o {$paths->file} -d {$paths->to} 2>&1 | sed -u 's/^/0: Extracting /' 
CMD;
    }

    public static function tgzCompressCmd($params) {

        $files = implode(' ', (array)Helper::escapeCmdArgs($params->files));
        $archive = Helper::mb_escapeshellarg($params->archive);
        var_dump(__METHOD__, $params);

        return <<<CMD
{$params->binary} -C {$archive} -czvf {$files} | sed -u 's/^/0: Adding /'
CMD;
    }
    
    public static function tgzExtractCmd($params) {

        $paths = Helper::escapeCmdArgs($params);
        //extract($params);

        var_dump(__METHOD__, $params);

        return <<<CMD
{$params->binary} -xzvf {$paths->file} -C {$paths->to} 2>&1 | sed -u 's/^/0: Extracting /' 
CMD;

    }

    public static function tarExtractCmd($params) {

        $paths = Helper::escapeCmdArgs($params);
        //extract($params);

        var_dump(__METHOD__, $params);

        return <<<CMD
{$params->binary} -xvf {$paths->file} -C {$paths->to} 2>&1 | sed -u 's/^/0: Extracting /' 
CMD;

    }

    public static function tarCompressCmd($params) {

        //$paths = Helper::escapeCmdArgs($params);

        $options = $params->options;
        $files = implode(' ', (array)Helper::escapeCmdArgs($params->files));
        $archive = Helper::mb_escapeshellarg($params->archive);
        var_dump(__METHOD__, $params);

        return <<<CMD
{$params->binary} -C {$archive} -cvf {$files} | sed -u 's/^/0: Adding /'
CMD;
    }



    public static function rarExtractCmd($params) {

        $paths = Helper::escapeCmdArgs($params);
        return <<<CMD
{$paths->binary} x -ol -p- -or- {$paths->file} {$paths->to} 2>&1 | awk -F '[\\b[:blank:]]+' 'BEGIN {OFS=", "} {for (i=1;i<=NF;i++) { if ((i-1)%10==0) printf "\\n0: "; printf "%s ",\$i} fflush()}'
CMD;

    }

    public static function rarCompressCmd($params) {

        $options = $params->options;
        $files = implode(' ', (array)Helper::escapeCmdArgs($params->files));
        $archive = Helper::mb_escapeshellarg($params->archive);
  
 // var_dump('ESCAPED FILE NAMES: ', $files, (array)Helper::escapeCmdArgs($params->files), Helper::escapeCmdArgs($params->files));

        return <<<CMD
{$params->binary} a -ep1 -m{$options->comp} -ol {$options->multif} -v{$options->multif}- {$archive} {$files} 2>&1 | awk -F '[\\b[:blank:]]+' 'BEGIN {OFS=", "} {for (i=1;i<=NF;i++) { if ((i-1)%10==0) printf "\\n0: "; printf "%s ",\$i} fflush()}'
CMD;
    }

    public static function bzipExtractCmd($params) {
        $paths = Helper::escapeCmdArgs($params);

        return <<<CMD
{$params->binary} -xjvf  {$paths->file} -C {$paths->to} 2>&1 | sed -u 's/^/0: Extracting /'
CMD;

    }

    public static function bzipCompressCmd($params) {

        $files = implode(' ', (array)Helper::escapeCmdArgs($params->files));
        $archive = Helper::mb_escapeshellarg($params->archive);

        return <<<CMD
{$params->binary} -C {$archive} -cjvf {$files} | sed -u 's/^/0: Adding /'
CMD;
    }

    public static function isoExtractCmd() {
        return <<<CMD
{$params->binary} x -bd -y -o {$to} {$file} 2>&1 | sed -u 's/^/0: Extracting /'
CMD;

    }

}
