import { Pipe, PipeTransform } from '@angular/core';
import { districtLabel } from '../order-labels';

@Pipe({ name: 'district' })
export class DistrictPipe implements PipeTransform {
  transform(code: string | null): string {
    return districtLabel(code);
  }
}
