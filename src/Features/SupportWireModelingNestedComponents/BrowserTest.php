<?php

namespace Livewire\Features\SupportWireModelingNestedComponents;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Rule;
use Livewire\Form;
use Livewire\Livewire;
use Sushi\Sushi;

/** @group morphing */
class BrowserTest extends \Tests\BrowserTestCase
{
    /** @test */
    public function can_bind_a_property_from_parent_to_property_from_child()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public $foo = 0;

                public function render() { return <<<'HTML'
                <div>
                    <span dusk="parent">Parent: {{ $foo }}</span>
                    <span x-text="$wire.foo" dusk="parent.ephemeral"></span>

                    <livewire:child wire:model="foo" />

                    <button wire:click="$refresh" dusk="refresh">refresh</button>
                </div>
                HTML; }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;

                public function render() { return <<<'HTML'
                <div>
                    <span dusk="child">Child: {{ $bar }}</span>
                    <span x-text="$wire.bar" dusk="child.ephemeral"></span>
                    <button wire:click="bar++" dusk="increment">increment</button>
                </div>
                HTML; }
            },
        ])
        ->assertSeeIn('@parent', 'Parent: 0')
        ->assertSeeIn('@child', 'Child: 0')
        ->assertSeeIn('@parent.ephemeral', '0')
        ->assertSeeIn('@child.ephemeral', '0')
        ->click('@increment')
        ->assertSeeIn('@parent', 'Parent: 0')
        ->assertSeeIn('@child', 'Child: 0')
        ->assertSeeIn('@parent.ephemeral', '1')
        ->assertSeeIn('@child.ephemeral', '1')
        ->waitForLivewire()->click('@refresh')
        ->assertSeeIn('@parent', 'Parent: 1')
        ->assertSeeIn('@child', 'Child: 1')
        ->assertSeeIn('@parent.ephemeral', '1')
        ->assertSeeIn('@child.ephemeral', '1');
    }

    /** @test */
    public function can_bind_a_property_from_parent_array_to_property_from_child()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public $foo = ['bar' => 'baz'];

                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $foo['bar'] }}</span>
                    <span x-text="$wire.foo['bar']" dusk='parent.ephemeral'></span>

                    <livewire:child wire:model='foo.bar' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;

                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='child'>Child: {{ $bar }}</span>
                    <span x-text='$wire.bar' dusk='child.ephemeral'></span>
                    <input type='text' wire:model='bar' dusk='child.input' />
                </div>
                HTML;
                }
            },
        ])
        ->assertDontSee('Property [$foo.bar] not found')
        ->assertSeeIn('@parent', 'Parent: baz')
        ->assertSeeIn('@child', 'Child: baz')
        ->assertSeeIn('@parent.ephemeral', 'baz')
        ->assertSeeIn('@child.ephemeral', 'baz')
        ->type('@child.input', 'qux')
        ->assertSeeIn('@parent', 'Parent: baz')
        ->assertSeeIn('@child', 'Child: baz')
        ->assertSeeIn('@parent.ephemeral', 'qux')
        ->assertSeeIn('@child.ephemeral', 'qux')
        ->waitForLivewire()->click('@refresh')
        ->assertSeeIn('@parent', 'Parent: qux')
        ->assertSeeIn('@child', 'Child: qux')
        ->assertSeeIn('@parent.ephemeral', 'qux')
        ->assertSeeIn('@child.ephemeral', 'qux');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_array_with_nested_array_to_property_from_child()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public $foo = [
                    'bar' => [
                        [ 'baz' => 'baz' ]
                    ],
                ];
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $foo['bar'][0]['baz'] }}</span>
                    <span x-text="$wire.foo['bar'][0]['baz']" dusk='parent.ephemeral'></span>

                    <livewire:child wire:model='foo.bar' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='child'>Child: {{ $bar[0]['baz'] }}</span>
                    <span x-text="$wire.bar[0]['baz']" dusk='child.ephemeral'></span>
                    <input type='text' wire:model='bar.0.baz' dusk='child.input' />
                </div>
                HTML;
                }
            },
        ])
            ->assertDontSee('Property [$foo.bar.0.baz] not found')
            ->assertSeeIn('@parent', 'Parent: baz')
            ->assertSeeIn('@child', 'Child: baz')
            ->assertSeeIn('@parent.ephemeral', 'baz')
            ->assertSeeIn('@child.ephemeral', 'baz')
            ->type('@child.input', 'qux')
            ->assertSeeIn('@parent', 'Parent: baz')
            ->assertSeeIn('@child', 'Child: baz')
            ->assertSeeIn('@parent.ephemeral', 'qux')
            ->assertSeeIn('@child.ephemeral', 'qux')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: qux')
            ->assertSeeIn('@child', 'Child: qux')
            ->assertSeeIn('@parent.ephemeral', 'qux')
            ->assertSeeIn('@child.ephemeral', 'qux');
    }

    /** @test */
    public function can_bind_a_property_from_parent_form_to_property_from_child()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public CreatePost $form;

                public function submit()
                {
                    $this->form->store();
                }

                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $form->title }}</span>
                    <span x-text='$wire.form.title' dusk='parent.ephemeral'></span>

                    <livewire:child wire:model='form.title' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                    <button wire:click='submit' dusk='submit'>submit</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;

                public function render()
                {
                    return <<<'HTML'
                        <div>
                            <span dusk='child'>Child: {{ $bar }}</span>
                            <span x-text='$wire.bar' dusk='child.ephemeral'></span>
                            <input type='text' wire:model='bar' dusk='child.input' />
                        </div>
                    HTML;
                }
            },
        ])
        ->assertDontSee('Property [$form.title] not found')
        ->assertSeeIn('@parent', 'Parent:')
        ->assertSeeIn('@child', 'Child:')
        ->assertSeeNothingIn('@parent.ephemeral')
        ->assertSeeNothingIn('@child.ephemeral')
        ->type('@child.input', 'foo')
        ->assertSeeIn('@parent', 'Parent:')
        ->assertSeeIn('@child', 'Child:')
        ->assertSeeIn('@parent.ephemeral', 'foo')
        ->assertSeeIn('@child.ephemeral', 'foo')
        ->waitForLivewire()->click('@refresh')
        ->assertSeeIn('@parent', 'Parent: foo')
        ->assertSeeIn('@child', 'Child: foo')
        ->assertSeeIn('@parent.ephemeral', 'foo')
        ->assertSeeIn('@child.ephemeral', 'foo')
        ->waitForLivewire()->click('@submit')
        ->assertSeeNothingIn('@parent.ephemeral', '')
        ->assertSeeNothingIn('@child.ephemeral', '');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_form_with_nested_array_to_property_from_child()
    {
        $test = Livewire::visit([
            new class extends \Livewire\Component {
                public CreatePostUsingNestedArray $form;
                
                public function submit()
                {
                    $this->form->store();
                }
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $form->entries['bar'][0]['baz'] }}</span>
                    <span x-text="$wire.form.entries['bar'][0]['baz']" dusk='parent.ephemeral'></span>

                    <livewire:child wire:model='form.entries' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                    <button wire:click='submit' dusk='submit'>submit</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $entry;
                
                public function render()
                {
                    return <<<'HTML'
                        <div>
                            <span dusk='child'>Child: {{ $entry['bar'][0]['baz'] }}</span>
                            <span x-text="$wire.entry['bar'][0]['baz']" dusk='child.ephemeral'></span>
                            <input type='text' wire:model='entry.bar.0.baz' dusk='child.input' />
                        </div>
                    HTML;
                }
            },
        ])
            ->assertDontSee('Property [$form.entries.bar.0.baz] not found')
            ->assertSeeIn('@parent', 'Parent:')
            ->assertSeeIn('@child', 'Child:')
            ->assertSeeNothingIn('@parent.ephemeral')
            ->assertSeeNothingIn('@child.ephemeral')
            ->type('@child.input', 'foo')
            ->assertSeeIn('@parent', 'Parent:')
            ->assertSeeIn('@child', 'Child:')
            ->assertSeeIn('@parent.ephemeral', 'foo')
            ->assertSeeIn('@child.ephemeral', 'foo')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: foo')
            ->assertSeeIn('@child', 'Child: foo')
            ->assertSeeIn('@parent.ephemeral', 'foo')
            ->assertSeeIn('@child.ephemeral', 'foo')
            ->waitForLivewire()->click('@submit')
            ->assertSeeNothingIn('@parent.ephemeral', '')
            ->assertSeeNothingIn('@child.ephemeral', '');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_to_property_from_child_ignoring_modifiers()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public $foo = 0;
                
                public function render() { return <<<'HTML'
                <div>
                    <span dusk="parent">Parent: {{ $foo }}</span>
                    <span x-text="$wire.foo" dusk="parent.ephemeral"></span>

                    <livewire:child wire:model.live="foo" />

                    <button wire:click="$refresh" dusk="refresh">refresh</button>
                </div>
                HTML; }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;
                
                public function render() { return <<<'HTML'
                <div>
                    <span dusk="child">Child: {{ $bar }}</span>
                    <span x-text="$wire.bar" dusk="child.ephemeral"></span>
                    <button wire:click="bar++" dusk="increment">increment</button>
                </div>
                HTML; }
            },
        ])
            ->assertSeeIn('@parent', 'Parent: 0')
            ->assertSeeIn('@child', 'Child: 0')
            ->assertSeeIn('@parent.ephemeral', '0')
            ->assertSeeIn('@child.ephemeral', '0')
            ->click('@increment')
            ->assertSeeIn('@parent', 'Parent: 0')
            ->assertSeeIn('@child', 'Child: 0')
            ->assertSeeIn('@parent.ephemeral', '1')
            ->assertSeeIn('@child.ephemeral', '1')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: 1')
            ->assertSeeIn('@child', 'Child: 1')
            ->assertSeeIn('@parent.ephemeral', '1')
            ->assertSeeIn('@child.ephemeral', '1');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_array_to_property_from_child_ignoring_modifiers()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public $foo = ['bar' => 'baz'];
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $foo['bar'] }}</span>
                    <span x-text="$wire.foo['bar']" dusk='parent.ephemeral'></span>

                    <livewire:child wire:model.debounce='foo.bar' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='child'>Child: {{ $bar }}</span>
                    <span x-text='$wire.bar' dusk='child.ephemeral'></span>
                    <input type='text' wire:model='bar' dusk='child.input' />
                </div>
                HTML;
                }
            },
        ])
            ->assertDontSee('Property [$foo.bar] not found')
            ->assertSeeIn('@parent', 'Parent: baz')
            ->assertSeeIn('@child', 'Child: baz')
            ->assertSeeIn('@parent.ephemeral', 'baz')
            ->assertSeeIn('@child.ephemeral', 'baz')
            ->type('@child.input', 'qux')
            ->assertSeeIn('@parent', 'Parent: baz')
            ->assertSeeIn('@child', 'Child: baz')
            ->assertSeeIn('@parent.ephemeral', 'qux')
            ->assertSeeIn('@child.ephemeral', 'qux')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: qux')
            ->assertSeeIn('@child', 'Child: qux')
            ->assertSeeIn('@parent.ephemeral', 'qux')
            ->assertSeeIn('@child.ephemeral', 'qux');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_array_with_nested_array_to_property_from_child_ignoring_modifiers()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public $foo = [
                    'bar' => [
                        [ 'baz' => 'baz' ]
                    ],
                ];
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $foo['bar'][0]['baz'] }}</span>
                    <span x-text="$wire.foo['bar'][0]['baz']" dusk='parent.ephemeral'></span>

                    <livewire:child wire:model.change='foo.bar' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='child'>Child: {{ $bar[0]['baz'] }}</span>
                    <span x-text="$wire.bar[0]['baz']" dusk='child.ephemeral'></span>
                    <input type='text' wire:model='bar.0.baz' dusk='child.input' />
                </div>
                HTML;
                }
            },
        ])
            ->assertDontSee('Property [$foo.bar.0.baz] not found')
            ->assertSeeIn('@parent', 'Parent: baz')
            ->assertSeeIn('@child', 'Child: baz')
            ->assertSeeIn('@parent.ephemeral', 'baz')
            ->assertSeeIn('@child.ephemeral', 'baz')
            ->type('@child.input', 'qux')
            ->assertSeeIn('@parent', 'Parent: baz')
            ->assertSeeIn('@child', 'Child: baz')
            ->assertSeeIn('@parent.ephemeral', 'qux')
            ->assertSeeIn('@child.ephemeral', 'qux')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: qux')
            ->assertSeeIn('@child', 'Child: qux')
            ->assertSeeIn('@parent.ephemeral', 'qux')
            ->assertSeeIn('@child.ephemeral', 'qux');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_form_to_property_from_child_ignoring_modifiers()
    {
        Livewire::visit([
            new class extends \Livewire\Component {
                public CreatePost $form;
                
                public function submit()
                {
                    $this->form->store();
                }
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $form->title }}</span>
                    <span x-text='$wire.form.title' dusk='parent.ephemeral'></span>

                    <livewire:child wire:model.blur='form.title' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                    <button wire:click='submit' dusk='submit'>submit</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $bar;
                
                public function render()
                {
                    return <<<'HTML'
                        <div>
                            <span dusk='child'>Child: {{ $bar }}</span>
                            <span x-text='$wire.bar' dusk='child.ephemeral'></span>
                            <input type='text' wire:model='bar' dusk='child.input' />
                        </div>
                    HTML;
                }
            },
        ])
            ->assertDontSee('Property [$form.title] not found')
            ->assertSeeIn('@parent', 'Parent:')
            ->assertSeeIn('@child', 'Child:')
            ->assertSeeNothingIn('@parent.ephemeral')
            ->assertSeeNothingIn('@child.ephemeral')
            ->type('@child.input', 'foo')
            ->assertSeeIn('@parent', 'Parent:')
            ->assertSeeIn('@child', 'Child:')
            ->assertSeeIn('@parent.ephemeral', 'foo')
            ->assertSeeIn('@child.ephemeral', 'foo')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: foo')
            ->assertSeeIn('@child', 'Child: foo')
            ->assertSeeIn('@parent.ephemeral', 'foo')
            ->assertSeeIn('@child.ephemeral', 'foo')
            ->waitForLivewire()->click('@submit')
            ->assertSeeNothingIn('@parent.ephemeral', '')
            ->assertSeeNothingIn('@child.ephemeral', '');
    }
    
    /** @test */
    public function can_bind_a_property_from_parent_form_with_nested_array_to_property_from_child_ignoring_modifiers()
    {
        $test = Livewire::visit([
            new class extends \Livewire\Component {
                public CreatePostUsingNestedArray $form;
                
                public function submit()
                {
                    $this->form->store();
                }
                
                public function render()
                {
                    return <<<'HTML'
                <div>
                    <span dusk='parent'>Parent: {{ $form->entries['bar'][0]['baz'] }}</span>
                    <span x-text="$wire.form.entries['bar'][0]['baz']" dusk='parent.ephemeral'></span>

                    <livewire:child wire:model.throttle='form.entries' />

                    <button wire:click='$refresh' dusk='refresh'>refresh</button>
                    <button wire:click='submit' dusk='submit'>submit</button>
                </div>
                HTML;
                }
            },
            'child' => new class extends \Livewire\Component {
                #[BaseModelable]
                public $entry;
                
                public function render()
                {
                    return <<<'HTML'
                        <div>
                            <span dusk='child'>Child: {{ $entry['bar'][0]['baz'] }}</span>
                            <span x-text="$wire.entry['bar'][0]['baz']" dusk='child.ephemeral'></span>
                            <input type='text' wire:model='entry.bar.0.baz' dusk='child.input' />
                        </div>
                    HTML;
                }
            },
        ])
            ->assertDontSee('Property [$form.entries.bar.0.baz] not found')
            ->assertSeeIn('@parent', 'Parent:')
            ->assertSeeIn('@child', 'Child:')
            ->assertSeeNothingIn('@parent.ephemeral')
            ->assertSeeNothingIn('@child.ephemeral')
            ->type('@child.input', 'foo')
            ->assertSeeIn('@parent', 'Parent:')
            ->assertSeeIn('@child', 'Child:')
            ->assertSeeIn('@parent.ephemeral', 'foo')
            ->assertSeeIn('@child.ephemeral', 'foo')
            ->waitForLivewire()->click('@refresh')
            ->assertSeeIn('@parent', 'Parent: foo')
            ->assertSeeIn('@child', 'Child: foo')
            ->assertSeeIn('@parent.ephemeral', 'foo')
            ->assertSeeIn('@child.ephemeral', 'foo')
            ->waitForLivewire()->click('@submit')
            ->assertSeeNothingIn('@parent.ephemeral', '')
            ->assertSeeNothingIn('@child.ephemeral', '');
    }
}



class CreatePost extends Form
{
    #[Rule('required')]
    public $title;

    public function store()
    {
        Post::create($this->all());

        $this->reset();
    }
}

class CreatePostUsingNestedArray extends Form
{
    #[Rule('required')]
    public $entries = [
        'bar' => [
            [ 'baz' => null ]
        ]
    ];
    
    public function store()
    {
        Post::create([
            'title' => $this->entries['bar'][0]['baz']
        ]);
        
        $this->reset();
    }
}

class Post extends Model
{
    use Sushi;

    protected $rows = [
        ['id' => 1, 'title' => 'foo'],
        ['id' => 2, 'title' => 'bar'],
    ];

    protected $fillable = ['title'];
}
