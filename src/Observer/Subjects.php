<?php declare(strict_types=1);

namespace SandwaveIo\Office365\Observer;

use SandwaveIo\Office365\Enum\Event;

class Subjects
{
    private array $subject = [];

    public function attach(string $event, $callback)
    {
        switch ($event) {

            case Event::CUSTOMER_CREATE:

                $observer = new CustomerObserver($callback);
                $subject = new CustomerSubject();
                break;
        }

        if (!array_key_exists($event, $this->subject)) {
            $this->subject[$event] = $subject;
        }

        $this->subject[$event]->attach($observer);
    }

    public function getSubject(string $event, $entity): ?\SplSubject
    {
        if (array_key_exists($event, $this->subject)) {

            switch ($event) {
                case Event::CUSTOMER_CREATE:
                    $this->subject[$event]->setCustomer($entity);
                    break;
            }

            return $this->subject[$event];
        }

        return null;
    }
}
