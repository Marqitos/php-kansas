<?php

class Kansas_Controllers_Admin
	extends Kansas_Controller_Abstract {

	public function Index($vars = []) {
    $counts = [];
    if(isset($vars['tasks'])) {
      $counts['tasks'] = [
        'counts'        => 0,
        'panel'         => 'panel-green',
        'icon'          => 'fa-tasks',
        'text'          => 'Nuevas tareas'
        ];
      foreach($vars['tasks'] as $slug => $task) {
        if(isset($task['notifications'])) {
          $counts['tasks']['counts'] += $task['notifications']['count'];
        }
      }
    }
		return $this->createViewResult('admin.dashboard.tpl', [
      'counts' => $counts
    ]);
	}
  
  public function dispatch($vars = []) {
		return $this->createViewResult('admin.dispatch.tpl');
  }
  
  public function tasks($vars = []) {
		return $this->createViewResult('admin.tasks.tpl');
  }
}