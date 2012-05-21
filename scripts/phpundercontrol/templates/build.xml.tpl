{use $componentName, $componentDeps = array(), $needsDatabase, $phps, $dsns, $phpCcVersion}
<?xml version="1.0" encoding="UTF-8"?>
<project name="ezc{$componentName}" default="build" basedir=".">

 <!-- Clean up old artifacts -->

 <target name="clean">
  <delete>
   <fileset dir="$\{basedir\}/build/logs" includes="**.*" />
  </delete>
  <delete>
   <fileset dir="$\{basedir\}/build/api" includes="**.*" />
  </delete>
  <delete>
   <fileset dir="$\{basedir\}/build/coverage" includes="**.*" />
  </delete>
 </target>  

 <!-- Update the SVN directories -->

 <target name="checkout">

  <!-- update ezc scripts -->
  <exec dir="$\{basedir\}/source/scripts" executable="svn">
   <arg line="up"/>
  </exec>

  <!-- update base component -->
  <exec dir="$\{basedir\}/source/trunk/Base" executable="svn">
   <arg line="up"/>
  </exec>

  <!-- console tools, used by unit test -->
  <exec dir="$\{basedir\}/source/trunk/ConsoleTools" executable="svn">
   <arg line="up"/>
  </exec>

  <!-- update unit test component -->
  <exec dir="$\{basedir\}/source/trunk/UnitTest" executable="svn">
   <arg line="up"/>
  </exec>

  <!-- component itself -->
  <exec dir="$\{basedir\}/source/trunk/{$componentName}" executable="svn">
   <arg line="up"/>
  </exec>

  <!-- update dependent components -->
  {foreach $componentDeps as $depComponentName}
  <exec dir="$\{basedir\}/source/trunk/{$depComponentName}" executable="svn">
   <arg line="up"/>
  </exec>
  {/foreach}

  <!-- re-setup ezc environment, for new components -->
  <exec dir="$\{basedir\}/source" executable="./scripts/setup-env.sh"/>

 </target>
 
 <!-- build docs -->
 <target name="phpdoc">

  <exec dir="$\{basedir\}/source/trunk/{$componentName}/src" executable="phpdoc" logerror="on">
   <arg line="--title '$\{ant.project.name\}' -ct type -ue on -i 'autoload*' -t $\{basedir\}/build/api -tb /local/ezctest/php/pear/PEAR/data/phpUnderControl/data/phpdoc/ -o HTML:Phpuc:phpuc -d ."/>
  </exec>

 </target>

 <!-- check coding style -->
 <target name="phpcs">

  <exec dir="$\{basedir\}/source/trunk/" executable="phpcs" output="$\{basedir\}/build/logs/checkstyle.xml" error="$\{basedir\}/build/logs/checkstyle.error.xml">
   <arg line="--report=checkstyle --standard=EZC --ignore=tests {$componentName}/src" />
  </exec>

 </target>

 <!-- build unittests -->
 {var
    $logFile = '',
    $target  = '',
    $logs    = array(),
    $targets = array()
 }

 {foreach $phps as $php}

   {if $needsDatabase}
     
     {foreach $dsns as $dsnName => $dsn}
       
       {$target  = 'php-' . $php . '-' . $dsnName}
       {$logFile = '${basedir}/build/tmp/' . $target . '.xml'}
  
       <target name="{$target}">
        <exec dir="$\{basedir\}/source/trunk/" executable="php-{$php}" failonerror="false">
<!-- - -log-metrics   '$\{basedir\}/build/logs/phpunit.metrics.xml' -->
         <arg line="UnitTest/src/runtests.php
                    {if $php == $phpCcVersion}
                      --coverage-html '$\{basedir\}/build/coverage'
                      --log-pmd       '$\{basedir\}/build/logs/phpunit.pmd.xml'
                      --coverage-xml  '$\{basedir\}/build/logs/phpunit.coverage.xml'
                    {/if}
                    --log-xml     '{$logFile}'
                    -D            '{$dsn}'
                    {$componentName}"/>
        </exec>
       </target>
       {$logs[] = $logFile}
       {$targets[] = $target}

     {/foreach}
  
   {else}
  
       {$target  = 'php-' . $php}
       {$logFile = '${basedir}/build/tmp/' . $target . '.xml'}
  
       <target name="{$target}">
        <exec dir="$\{basedir\}/source/trunk/" executable="php-{$php}" failonerror="false">
<!-- - -log-metrics   '$\{basedir\}/build/logs/phpunit.metrics.xml' -->
         <arg line="UnitTest/src/runtests.php
                    {if $php == $phpCcVersion}
                      --coverage-html '$\{basedir\}/build/coverage'
                      --log-pmd       '$\{basedir\}/build/logs/phpunit.pmd.xml'
                      --coverage-xml  '$\{basedir\}/build/logs/phpunit.coverage.xml'
                    {/if}
                    --log-xml     '{$logFile}'
                    {$componentName}"/>
        </exec>
       </target>

       {$logs[] = $logFile}
       {$targets[] = $target}
  
   {/if}

 {/foreach}
     
 {* Create merge for all DSNs *}
  
 <!-- Merge log files from different DSNs and PHP versions -->
 <target name="merge">
    <exec executable="phpuc" dir="$\{basedir\}" failonerror="true">
      <arg line="merge-phpunit
                 -b {str_join( $targets, ',' )}
                 -i {str_join( $logs   , ',' )}
                 -o $\{basedir\}/build/logs/log.xml"/>
    </exec>
  </target>
  

 <!-- clean up unit test directory -->
 <target name="cleanup">
  <delete dir="$\{basedir\}/source/trunk/run-tests-tmp"/>
  <delete>
          <fileset dir="$\{basedir\}/build/tmp">
            <include name="*"/>
          </fileset>
    </delete>
 </target>

 <!-- originally: <target name="build" depends="checkout,phpunit,cleanup"/> -->
 <!-- put parts together -->
 <target name="build" depends="clean,checkout,phpdoc,phpcs,{str_join( $targets, ',' )},merge,cleanup"/>
</project>
