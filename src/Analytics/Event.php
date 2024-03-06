<?php

namespace Utopia\Analytics;

class Event
{
    /**
     * @var string (required)
     */
    private string $type = '';

    /**
     * @var string (required)
     */
    private string $url = '';

    private string $name = '';

    private ?string $value = null;

    /**
     * @var array<string, mixed>
     */
    private array $props = [];

    /**
     * Get the type of event
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type of event
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the URL of the event
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the URL of the event
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the name of the event
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the event
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of the event
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the value of the event
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the properties of the event
     *
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        return $this->props;
    }

    /**
     * Adds extra properties to the event
     */
    public function addProp(string $key, string $value): self
    {
        $this->props[$key] = $value;

        return $this;
    }

    /**
     * Removes a property from the event
     */
    public function removeProp(string $key): self
    {
        if (array_key_exists($key, $this->props)) {
            unset($this->props[$key]);
        }

        return $this;
    }

    /**
     * Get a property from the event
     */
    public function getProp(string $key): mixed
    {
        if (array_key_exists($key, $this->props)) {
            return $this->props[$key];
        }

        return null;
    }

    /**
     * Set the properties of the event
     *
     * @param  array<string, mixed>  $props
     */
    public function setProps(array $props): self
    {
        $this->props = $props;

        return $this;
    }
}
