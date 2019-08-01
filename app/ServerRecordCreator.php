<?php

namespace App;

use App\Contracts\StackDefinition;

class ServerRecordCreator
{
    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * The stack definition instance.
     *
     * @var \App\Contracts\StackDefinition
     */
    public $definition;

    /**
     * Create a new stack server creator instance.
     *
     * @param  \App\Stack  $stack
     * @param  \App\Contracts\StackDefinition  $definition
     * @return void
     */
    public function __construct(Stack $stack, StackDefinition $definition)
    {
        $this->stack = $stack;
        $this->definition = $definition;
    }

    /**
     * Create the server records for the stack.
     *
     * @return void
     */
    public function create()
    {
        $definition = $this->definition->toArray();

        if (! isset($definition[$this->type]) || empty($definition[$this->type])) {
            return;
        }

        foreach (range(1, $definition[$this->type]['scale'] ?? 1) as $index) {
            $this->relation()->create(
                $this->baseAttributes($index, $definition[$this->type]) +
                $this->attributes($definition)
            );
        }
    }

    /**
     * Get the base server attributes for the given definition.
     *
     * @param  int  $index
     * @param  array  $definition
     * @return array
     */
    protected function baseAttributes($index, array $definition)
    {
        return [
            'project_id' => $this->stack->environment->project->id,
            'name' => "{$this->stack->name}-{$this->type}-{$index}",
            'size' => $definition['size'],
            'sudo_password' => str_random(40),
            'meta' => array_filter([
                'serves' => $definition['serves'] ?? null,
                'tls' => $definition['tls'] ?? null,
            ]),
        ];
    }

    /**
     * Get the custom attributes for the servers.
     *
     * @return array
     */
    protected function attributes()
    {
        return [];
    }
}
