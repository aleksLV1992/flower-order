import { Pipe, PipeTransform } from '@angular/core';
import { deliverySlot } from '../formatting';
import { DeliverySlot } from '../orders.models';

@Pipe({ name: 'deliverySlot' })
export class DeliverySlotPipe implements PipeTransform {
  transform(slot: DeliverySlot): string {
    return deliverySlot(slot);
  }
}
