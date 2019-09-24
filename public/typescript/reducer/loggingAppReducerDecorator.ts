import { bind, empty } from 'ramda';
import { ApplicationEvent } from '../model';
import { AppReducer } from './appReducer';

// tslint:disable-next-line:no-console
const log: (...args: any[]) => void = typeof console !== 'undefined' ? bind(console.info, console) : empty;

/**
 * Used for debugging purposes.
 */
export const loggingAppReducerDecorator = (reducer: AppReducer): AppReducer => {
  return (oldState, event) => {
    const newState = reducer(oldState, event);
    log(ApplicationEvent[event.type], event.timestamp, event.value, { oldState, newState });
    return newState;
  };
};
