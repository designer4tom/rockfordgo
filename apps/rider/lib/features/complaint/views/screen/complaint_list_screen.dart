import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/complaint_provider.dart';

class ComplaintListScreen extends StatefulWidget {
  const ComplaintListScreen({super.key});

  @override
  State<ComplaintListScreen> createState() => _ComplaintListScreenState();
}

class _ComplaintListScreenState extends State<ComplaintListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<ComplaintProvider>().loadComplaints(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ComplaintProvider>();

    return Scaffold(
      appBar: AppBar(title: Text('complaint.title'.tr())),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/create-complaint'),
        icon: const Icon(Icons.add),
        label: Text('complaint.new'.tr()),
      ),
      body: provider.isLoading
          ? const LoadingWidget()
          : provider.complaints.isEmpty
              ? EmptyState(
                  icon: Icons.report_problem_outlined,
                  title: 'complaint.empty'.tr(),
                )
              : ListView(
                  children: provider.complaints.map((c) {
                    return ListTile(
                      leading: const Icon(Icons.report_problem_outlined),
                      title: Text(c.category),
                      subtitle: Text(c.description,
                          maxLines: 2, overflow: TextOverflow.ellipsis),
                      trailing: Text(
                        c.status,
                        style: const TextStyle(
                            color: AppColors.primary,
                            fontWeight: FontWeight.w600),
                      ),
                    );
                  }).toList(),
                ),
    );
  }
}
