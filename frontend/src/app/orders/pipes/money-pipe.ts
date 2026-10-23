import { Pipe, PipeTransform } from '@angular/core';
import { money } from '../formatting';

@Pipe({ name: 'money' })
export class MoneyPipe implements PipeTransform {
  transform(kopecks: number): string {
    return money(kopecks);
  }
}
