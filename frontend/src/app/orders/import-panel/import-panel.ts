import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { OUTCOME_LABELS, OUTCOMES, STATUS_LABELS, errorMessage, isProblem } from '../order-labels';
import { ImportOutcome, ImportReport, ImportResult } from '../orders.models';
import { OrdersApi } from '../orders-api';
import { OrdersStore } from '../orders-store';

type Filter = ImportOutcome | 'problems' | 'all';

type State =
  | { kind: 'idle' }
  | { kind: 'loading' }
  | { kind: 'done'; report: ImportReport }
  | { kind: 'error'; message: string };

@Component({
  selector: 'app-import-panel',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './import-panel.html',
  styleUrl: './import-panel.scss',
})
export class ImportPanel {
  private readonly api = inject(OrdersApi);
  private readonly orders = inject(OrdersStore);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly state = signal<State>({ kind: 'idle' });
  protected readonly outcomeFilter = signal<Filter>('problems');
  protected readonly outcomes = OUTCOMES;
  protected readonly outcomeLabels = OUTCOME_LABELS;
  protected readonly statusLabels = STATUS_LABELS;

  protected readonly loading = computed(() => this.state().kind === 'loading');
  protected readonly report = computed(() => {
    const state = this.state();
    return state.kind === 'done' ? state.report : null;
  });
  protected readonly error = computed(() => {
    const state = this.state();
    return state.kind === 'error' ? state.message : null;
  });
  protected readonly visibleResults = computed<ImportResult[]>(() => {
    const results = this.report()?.results ?? [];
    const filter = this.outcomeFilter();
    if (filter === 'all') {
      return results;
    }
    if (filter === 'problems') {
      return results.filter(isProblem);
    }
    return results.filter((r) => r.outcome === filter);
  });
  protected readonly hiddenCount = computed(
    () => (this.report()?.results.length ?? 0) - this.visibleResults().length,
  );

  run(): void {
    if (this.loading()) {
      return;
    }
    this.state.set({ kind: 'loading' });
    this.outcomeFilter.set('problems');
    this.api
      .importFixture()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (report) => {
          this.state.set({ kind: 'done', report });
          this.orders.reload();
        },
        error: (err: unknown) => {
          this.state.set({ kind: 'error', message: errorMessage(err) });
        },
      });
  }

  protected toggleFilter(outcome: ImportOutcome): void {
    this.outcomeFilter.update((current) => (current === outcome ? 'problems' : outcome));
  }

  protected showAll(): void {
    this.outcomeFilter.set('all');
  }

  protected showProblems(): void {
    this.outcomeFilter.set('problems');
  }
}
