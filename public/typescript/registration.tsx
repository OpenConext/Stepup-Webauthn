import 'core-js';
import * as React from 'react';
import ReactDom from 'react-dom';
import { RegistrationContainer } from './components/RegistrationContainer';
import { translate } from './translations';

ReactDom.render(<RegistrationContainer t={translate}/>, document.getElementById('root'));
