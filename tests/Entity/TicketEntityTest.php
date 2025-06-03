<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Ticket;
use App\Entity\User;

class TicketEntityTest extends TestCase
{
    public function testTicketEntityExists(): void
    {
        $this->assertTrue(class_exists('App\Entity\Ticket'), 'Ticket entity does not exist.');
    }

    public function testTicketEntityHasRequiredProperties(): void
    {
        if (!class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Ticket entity does not exist yet - implement it first');
        }

        $reflector = new \ReflectionClass('App\Entity\Ticket');
        $properties = $reflector->getProperties();
        $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

        // Required properties according to specifications
        $requiredProperties = [
            'owner',         // utilisateur propriétaire du ticket
            'assignedTo',    // utilisateur à qui est assigné le ticket
            'createdAt',     // date de création
            'firstAssignedAt', // date de première assignation
            'lastAssignedAt',  // date de dernière assignation
            'title',         // titre du ticket
            'description',   // descriptif du ticket
            'priority',      // priorité du ticket (basse, normale, haute)
            'status'         // état du ticket (pending, waiting, in-progress, done)
        ];

        foreach ($requiredProperties as $property) {
            $this->assertContains(
                $property, 
                $propertyNames, 
                "Ticket entity should have a '$property' property"
            );
        }
    }

    public function testTicketEntityHasRequiredGetterMethods(): void
    {
        if (!class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Ticket entity does not exist yet - implement it first');
        }

        $reflector = new \ReflectionClass('App\Entity\Ticket');
        
        $requiredGetters = [
            'getOwner',
            'getAssignedTo',
            'getCreatedAt',
            'getFirstAssignedAt',
            'getLastAssignedAt',
            'getTitle',
            'getDescription',
            'getPriority',
            'getStatus'
        ];

        foreach ($requiredGetters as $getter) {
            $this->assertTrue(
                $reflector->hasMethod($getter),
                "Ticket entity should have a '$getter' method"
            );
        }
    }

    public function testTicketEntityHasRequiredSetterMethods(): void
    {
        if (!class_exists('App\Entity\Ticket')) {
            $this->markTestSkipped('Ticket entity does not exist yet - implement it first');
        }

        $reflector = new \ReflectionClass('App\Entity\Ticket');
        
        $requiredSetters = [
            'setOwner',
            'setAssignedTo',
            'setCreatedAt',
            'setFirstAssignedAt',
            'setLastAssignedAt',
            'setTitle',
            'setDescription',
            'setPriority',
            'setStatus'
        ];

        foreach ($requiredSetters as $setter) {
            $this->assertTrue(
                $reflector->hasMethod($setter),
                "Ticket entity should have a '$setter' method"
            );
        }
    }
}
