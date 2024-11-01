<?php


class Expertrecsearch_Loader {


	protected $actions;

	protected $filters;

	public function __construct() {
		do_action( 'er/init', 'In loader construct' );
		$this->actions = array();
		$this->filters = array();
	}

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		do_action(
			'er/init',
			'In add action enqueue for {hook} {callback}',
			array(
				'hook'     => $hook,
				'callback' => $callback,
			)
		);
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		do_action(
			'er/debug',
			'In add filters enqueue for {hook} {callback}',
			array(
				'hook',
				$hook,
				'callback' => $callback,
			)
		);
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function run() {
		do_action( 'er/init', 'loader run registering filters and hooks' );

		foreach ( $this->filters as $hook ) {
			do_action( 'er/debug', 'adding filter ' . $hook['hook'] . 'callback' . $hook['callback'] );
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			do_action( 'er/init', 'adding action' . $hook['hook'] . 'callback' . $hook['callback'] );
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
