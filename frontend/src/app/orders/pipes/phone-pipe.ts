import { Pipe, PipeTransform } from '@angular/core';
import { phone } from '../formatting';

@Pipe({ name: 'phone' })
export class PhonePipe implements PipeTransform {
  transform(value: string): string {
    return phone(value);
  }
}
