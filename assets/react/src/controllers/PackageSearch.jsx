import React, { Component } from 'react';
import PackageList from '../components/PackageList.js';

export default class extends Component {
    constructor() {
      super();

      this.state = {
        search: ''
      }
    }

    render() {
        return (
            <div>
                <input
                    type="search"
                    placeholder="This search is built in React!"
                    className="w-full px-3 py-1.5 border border-solid border-border rounded-sm bg-body text-body-text focus:border-link focus:[outline:revert]"
                    value={this.state.search}
                    onChange={(event) => this.setState({search: event.target.value})}
                />

                <div className="mt-3">
                    <PackageList packages={this.filteredPackages()} />
                </div>
            </div>
        );
    }

    filteredPackages() {
        if (!this.state.search) {
            return this.props.packages;
        }

        return this.props.packages.filter((uxPackage) => {
            return uxPackage.humanName.toLowerCase().includes(this.state.search.toLowerCase())
        });
    }
}
