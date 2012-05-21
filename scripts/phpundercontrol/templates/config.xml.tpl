{use $components, $deps}
<cruisecontrol>
{foreach $components as $component}
  <project name="ezc{$component}" buildafterfailed="false" forceBuildNewProject="true">
    <plugin name="svn" classname="net.sourceforge.cruisecontrol.sourcecontrols.SVN"/>
    
    <listeners>
      <currentbuildstatuslistener file="logs/$\{project.name\}/status.txt"/>
    </listeners>

    <modificationset>
     <svn LocalWorkingCopy="projects/$\{project.name\}/source/trunk/{$component}/"/>
     {foreach $deps[$component] as $dep}
      <svn localWorkingCopy="projects/$\{project.name\}/source/trunk/{$dep}/"/>
     {/foreach}
    </modificationset>
    
    <schedule>
      <ant anthome="apache-ant-1.7.0" buildfile="projects/$\{project.name\}/build.xml"/>
    </schedule>
    
    <log dir="logs/$\{project.name\}">
      <merge dir="projects/$\{project.name\}/build/logs/"/>
    </log>
    
    <publishers>
      <currentbuildstatuspublisher file="logs/$\{project.name\}/buildstatus.txt"/>
      <artifactspublisher dir="projects/$\{project.name\}/build/api" dest="logs/$\{project.name\}" subdirectory="api"/>
      <artifactspublisher dir="projects/$\{project.name\}/build/coverage" dest="logs/$\{project.name\}" subdirectory="coverage"/>
      <execute command="phpuc graph logs/$\{project.name\}"/>
    </publishers>
  </project>
{/foreach}
</cruisecontrol>
