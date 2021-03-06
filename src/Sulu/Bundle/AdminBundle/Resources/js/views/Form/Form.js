// @flow
import type {ElementRef} from 'react';
import React from 'react';
import type {IObservableValue} from 'mobx';
import {action, computed, toJS, isObservableArray, observable} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import Dialog from '../../components/Dialog';
import PublishIndicator from '../../components/PublishIndicator';
import {default as FormContainer, ResourceFormStore} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {AttributeMap, Route, UpdateRouteMethod} from '../../services/Router/types';
import ResourceStore from '../../stores/ResourceStore';
import {translate} from '../../utils/Translator';
import formToolbarActionRegistry from './registries/FormToolbarActionRegistry';
import formStyles from './form.scss';

type Props = ViewProps & {
    locales: Array<string>,
    resourceStore: ResourceStore,
    title?: string,
};

const FORM_STORE_UPDATE_ROUTE_HOOK_PRIORITY = 2048;

const HAS_CHANGED_ERROR_CODE = 1102;

@observer
class Form extends React.Component<Props> {
    resourceStore: ResourceStore;
    resourceFormStore: ResourceFormStore;
    form: ?ElementRef<typeof FormContainer>;
    @observable errors = [];
    showSuccess: IObservableValue<boolean> = observable.box(false);
    @observable toolbarActions = [];
    @observable showDirtyWarning: boolean = false;
    @observable showHasChangedWarning: boolean = false;
    postponedSaveOptions: Object;
    postponedUpdateRouteMethod: ?UpdateRouteMethod;
    postponedRoute: ?Route;
    postponedRouteAttributes: ?AttributeMap;
    checkFormStoreDirtyStateBeforeNavigationDisposer: () => void;

    @computed get hasOwnResourceStore() {
        const {
            resourceStore,
            route: {
                options: {
                    resourceKey,
                },
            },
        } = this.props;

        return resourceKey && resourceStore.resourceKey !== resourceKey;
    }

    @computed get locales() {
        const {
            locales: propsLocales,
            route: {
                options: {
                    locales: routeLocales,
                },
            },
        } = this.props;

        return routeLocales ? routeLocales : propsLocales;
    }

    constructor(props: Props) {
        super(props);

        const {resourceStore, router} = this.props;
        const {
            attributes,
            route: {
                options: {
                    apiOptions = {},
                    formKey,
                    idQueryParameter,
                    resourceKey,
                    routerAttributesToFormStore = {},
                },
            },
        } = router;
        const {id} = attributes;

        if (!resourceStore) {
            throw new Error(
                'The view "Form" needs a resourceStore to work properly.'
                + 'Did you maybe forget to make this view a child of a "ResourceTabs" view?'
            );
        }

        if (!formKey) {
            throw new Error('The route does not define the mandatory "formKey" option');
        }

        const formStoreOptions = this.buildFormStoreOptions(apiOptions, attributes, routerAttributesToFormStore);

        if (this.hasOwnResourceStore) {
            let locale = resourceStore.locale;
            if (!locale && this.locales) {
                locale = observable.box();
            }

            if (idQueryParameter) {
                this.resourceStore = new ResourceStore(resourceKey, id, {locale}, formStoreOptions, idQueryParameter);
            } else {
                this.resourceStore = new ResourceStore(resourceKey, id, {locale}, formStoreOptions);
            }
        } else {
            this.resourceStore = resourceStore;
        }

        this.resourceFormStore = new ResourceFormStore(this.resourceStore, formKey, formStoreOptions);

        if (this.resourceStore.locale) {
            router.bind('locale', this.resourceStore.locale);
        }

        this.checkFormStoreDirtyStateBeforeNavigationDisposer = router.addUpdateRouteHook(
            this.checkFormStoreDirtyStateBeforeNavigation,
            FORM_STORE_UPDATE_ROUTE_HOOK_PRIORITY
        );
    }

    @action checkFormStoreDirtyStateBeforeNavigation = (
        route: ?Route,
        attributes: ?AttributeMap,
        updateRouteMethod: ?UpdateRouteMethod
    ) => {
        if (!this.resourceFormStore.dirty) {
            return true;
        }

        if (
            this.showDirtyWarning === true
            && this.postponedRoute === route
            && equals(this.postponedRouteAttributes, attributes)
            && this.postponedUpdateRouteMethod === updateRouteMethod
        ) {
            // If the warning has already been displayed for the exact same route and attributes we can assume that the
            // confirm button in the warning has been clicked, since it calls the same routing action again.
            return true;
        }

        if (!route && !attributes && !updateRouteMethod) {
            // If none of these attributes are set the call comes because the user wants to close the window
            return false;
        }

        this.showDirtyWarning = true;
        this.postponedUpdateRouteMethod = updateRouteMethod;
        this.postponedRoute = route;
        this.postponedRouteAttributes = attributes;

        return false;
    };

    buildFormStoreOptions(
        apiOptions: Object,
        attributes: Object,
        routerAttributesToFormStore: {[string | number]: string}
    ) {
        const formStoreOptions = apiOptions ? apiOptions : {};

        routerAttributesToFormStore = toJS(routerAttributesToFormStore);
        Object.keys(routerAttributesToFormStore).forEach((key) => {
            const formOptionKey = routerAttributesToFormStore[key];
            const attributeName = isNaN(key) ? key : routerAttributesToFormStore[key];

            formStoreOptions[formOptionKey] = attributes[attributeName];
        });

        return formStoreOptions;
    }

    @action componentDidMount() {
        const {router} = this.props;
        const {
            route: {
                options: {
                    toolbarActions,
                },
            },
        } = router;

        if (!Array.isArray(toolbarActions) && !isObservableArray(toolbarActions)) {
            throw new Error('The view "Form" needs some defined toolbarActions to work properly!');
        }

        this.toolbarActions = toolbarActions.map((toolbarAction) => new (formToolbarActionRegistry.get(toolbarAction))(
            this.resourceFormStore,
            this,
            router,
            this.locales
        ));
    }

    componentDidUpdate(prevProps: Props) {
        if (!equals(this.props.locales, prevProps.locales)) {
            this.toolbarActions.forEach((toolbarAction) => {
                toolbarAction.setLocales(this.locales);
            });
        }
    }

    componentWillUnmount() {
        this.checkFormStoreDirtyStateBeforeNavigationDisposer();

        this.resourceFormStore.destroy();

        if (this.hasOwnResourceStore) {
            this.resourceStore.destroy();
        }
    }

    @action showSuccessSnackbar = () => {
        this.showSuccess.set(true);
    };

    @action submit = (action: ?string) => {
        if (!this.form) {
            throw new Error('The form ref has not been set! This should not happen and is likely a bug.');
        }
        this.form.submit(action);
    };

    handleSubmit = (actionParameter: ?string) => {
        return this.save({action: actionParameter});
    };

    save = (options: Object) => {
        const {resourceStore, router} = this.props;

        const {
            attributes,
            route: {
                options: {
                    editRoute,
                    routerAttributesToEditRoute,
                },
            },
        } = router;

        if (editRoute) {
            resourceStore.destroy();
        }

        const saveOptions = {...options};

        const editRouteParameters = {};

        if (routerAttributesToEditRoute) {
            Object.keys(toJS(routerAttributesToEditRoute)).forEach((key) => {
                const formOptionKey = routerAttributesToEditRoute[key];
                const attributeName = isNaN(key) ? key : routerAttributesToEditRoute[key];

                editRouteParameters[formOptionKey] = attributes[attributeName];
            });
        }

        return this.resourceFormStore.save(saveOptions)
            .then((response) => {
                this.showSuccessSnackbar();

                if (editRoute) {
                    router.navigate(
                        editRoute,
                        {
                            id: resourceStore.id,
                            locale: resourceStore.locale,
                            ...editRouteParameters,
                        }
                    );
                }

                return response;
            })
            .catch(action((error) => {
                if (error.code === HAS_CHANGED_ERROR_CODE) {
                    this.showHasChangedWarning = true;
                    this.postponedSaveOptions = options;
                    return;
                }

                this.errors.push(error);
            }));
    };

    handleError = () => {
        this.errors.push('Errors occured when trying to save the data from the FormStore');
    };

    @action handleDirtyWarningCancelClick = () => {
        this.showDirtyWarning = false;
        this.postponedUpdateRouteMethod = undefined;
        this.postponedRoute = undefined;
        this.postponedRouteAttributes = undefined;
    };

    @action handleDirtyWarningConfirmClick = () => {
        if (!this.postponedUpdateRouteMethod || !this.postponedRoute || !this.postponedRouteAttributes) {
            throw new Error('Some routing information is missing. This should not happen and is likely a bug.');
        }

        this.postponedUpdateRouteMethod(this.postponedRoute.name, this.postponedRouteAttributes);
        this.postponedUpdateRouteMethod = undefined;
        this.postponedRoute = undefined;
        this.postponedRouteAttributes = undefined;
        this.showDirtyWarning = false;
    };

    @action handleHasChangedWarningCancelClick = () => {
        this.showHasChangedWarning = false;
        this.postponedSaveOptions = undefined;
    };

    @action handleHasChangedWarningConfirmClick = () => {
        this.save({...this.postponedSaveOptions, force: true});
        this.showHasChangedWarning = false;
        this.postponedSaveOptions = undefined;
    };

    setFormRef = (form: ?ElementRef<typeof FormContainer>) => {
        this.form = form;
    };

    render() {
        const {
            route: {
                options: {
                    titleVisible = false,
                },
            },
            router,
            title,
        } = this.props;

        return (
            <div className={formStyles.form}>
                {titleVisible && title && <h1>{title}</h1>}
                <FormContainer
                    onError={this.handleError}
                    onSubmit={this.handleSubmit}
                    ref={this.setFormRef}
                    router={router}
                    store={this.resourceFormStore}
                />
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmText={translate('sulu_admin.confirm')}
                    onCancel={this.handleDirtyWarningCancelClick}
                    onConfirm={this.handleDirtyWarningConfirmClick}
                    open={this.showDirtyWarning}
                    title={translate('sulu_admin.dirty_warning_dialog_title')}
                >
                    {translate('sulu_admin.dirty_warning_dialog_text')}
                </Dialog>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmText={translate('sulu_admin.confirm')}
                    onCancel={this.handleHasChangedWarningCancelClick}
                    onConfirm={this.handleHasChangedWarningConfirmClick}
                    open={this.showHasChangedWarning}
                    title={translate('sulu_admin.has_changed_warning_dialog_title')}
                >
                    {translate('sulu_admin.has_changed_warning_dialog_text')}
                </Dialog>
            </div>
        );
    }
}

export default withToolbar(Form, function() {
    const {router} = this.props;
    const {
        attributes,
        route: {
            options: {
                backRoute,
                routerAttributesToBackRoute,
            },
        },
    } = router;
    const {errors, resourceStore, showSuccess} = this;

    const backButton = backRoute
        ? {
            onClick: () => {
                const backRouteParameters = {};

                if (routerAttributesToBackRoute) {
                    Object.keys(toJS(routerAttributesToBackRoute)).forEach((key) => {
                        const formOptionKey = routerAttributesToBackRoute[key];
                        const attributeName = isNaN(key) ? key : routerAttributesToBackRoute[key];

                        backRouteParameters[formOptionKey] = attributes[attributeName];
                    });
                }

                if (resourceStore.locale) {
                    backRouteParameters.locale = resourceStore.locale.get();
                }

                router.restore(backRoute, backRouteParameters);
            },
        }
        : undefined;
    const locale = this.locales
        ? {
            value: resourceStore.locale.get(),
            onChange: (locale) => {
                router.navigate(router.route.name, {...router.attributes, locale});
            },
            options: this.locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    const items = this.toolbarActions
        .map((toolbarAction) => toolbarAction.getToolbarItemConfig())
        .filter((item) => item != null);

    const icons = [];
    const formData = this.resourceFormStore.data;

    if (formData.hasOwnProperty('publishedState') || formData.hasOwnProperty('published')) {
        const {publishedState, published} = formData;
        icons.push(
            <PublishIndicator
                draft={publishedState === undefined ? false : !publishedState}
                key="publish"
                published={published === undefined ? false : !!published}
            />
        );
    }

    return {
        backButton,
        errors,
        locale,
        items,
        icons,
        showSuccess,
    };
});
